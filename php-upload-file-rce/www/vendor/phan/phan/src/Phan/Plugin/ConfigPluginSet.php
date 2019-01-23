<?php declare(strict_types=1);

namespace Phan\Plugin;

use AssertionError;
use ast\Node;
use Closure;
use Phan\AST\Visitor\Element;
use Phan\CodeBase;
use Phan\Config;
use Phan\Daemon\Request;
use Phan\Exception\IssueException;
use Phan\Issue;
use Phan\Language\Context;
use Phan\Language\Element\Clazz;
use Phan\Language\Element\Func;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\Method;
use Phan\Language\Element\Property;
use Phan\Language\Element\TypedElement;
use Phan\Language\Element\UnaddressableTypedElement;
use Phan\Language\FQSEN;
use Phan\Language\Type;
use Phan\Language\UnionType;
use Phan\LanguageServer\CompletionRequest;
use Phan\LanguageServer\CompletionResolver;
use Phan\LanguageServer\DefinitionResolver;
use Phan\LanguageServer\GoToDefinitionRequest;
use Phan\Library\RAII;
use Phan\Plugin\Internal\ArrayReturnTypeOverridePlugin;
use Phan\Plugin\Internal\BuiltinSuppressionPlugin;
use Phan\Plugin\Internal\CallableParamPlugin;
use Phan\Plugin\Internal\ClosureReturnTypeOverridePlugin;
use Phan\Plugin\Internal\CompactPlugin;
use Phan\Plugin\Internal\DependentReturnTypeOverridePlugin;
use Phan\Plugin\Internal\MiscParamPlugin;
use Phan\Plugin\Internal\NodeSelectionPlugin;
use Phan\Plugin\Internal\NodeSelectionVisitor;
use Phan\Plugin\Internal\RequireExistsPlugin;
use Phan\Plugin\Internal\StringFunctionPlugin;
use Phan\Plugin\Internal\ThrowAnalyzerPlugin;
use Phan\Plugin\Internal\VariableTrackerPlugin;
use Phan\PluginV2;
use Phan\PluginV2\AfterAnalyzeFileCapability;
use Phan\PluginV2\AnalyzeClassCapability;
use Phan\PluginV2\AnalyzeFunctionCallCapability;
use Phan\PluginV2\AnalyzeFunctionCapability;
use Phan\PluginV2\AnalyzeMethodCapability;
use Phan\PluginV2\AnalyzePropertyCapability;
use Phan\PluginV2\BeforeAnalyzeCapability;
use Phan\PluginV2\BeforeAnalyzeFileCapability;
use Phan\PluginV2\FinalizeProcessCapability;
use Phan\PluginV2\PluginAwarePostAnalysisVisitor;
use Phan\PluginV2\PluginAwarePreAnalysisVisitor;
use Phan\PluginV2\PostAnalyzeNodeCapability;
use Phan\PluginV2\PreAnalyzeNodeCapability;
use Phan\PluginV2\ReturnTypeOverrideCapability;
use Phan\PluginV2\SuppressionCapability;
use Phan\Suggestion;
use ReflectionException;
use ReflectionProperty;
use Throwable;
use UnusedSuppressionPlugin;
use function is_null;

/**
 * The root plugin that calls out each hook
 * on any plugins defined in the configuration.
 *
 * (Note: This is called almost once per each AST node being analyzed.
 * Speed is preferred over using Phan\Memoize.)
 *
 * @phan-file-suppress PhanPluginDescriptionlessCommentOnPublicMethod TODO: Document
 */
final class ConfigPluginSet extends PluginV2 implements
    AfterAnalyzeFileCapability,
    AnalyzeClassCapability,
    AnalyzeFunctionCapability,
    AnalyzeFunctionCallCapability,
    AnalyzeMethodCapability,
    AnalyzePropertyCapability,
    BeforeAnalyzeCapability,
    BeforeAnalyzeFileCapability,
    FinalizeProcessCapability,
    ReturnTypeOverrideCapability,
    SuppressionCapability
{

    /** @var array<int,PluginV2>|null - Cached plugin set for this instance. Lazily generated. */
    private $plugin_set;

    /**
     * @var array<int,Closure>|null - plugins to analyze nodes in pre order.
     * @phan-var array<int,Closure(CodeBase,Context,Node):void>|null
     */
    private $pre_analyze_node_plugin_set;

    /**
     * @var array<int,Closure> - plugins to analyze files
     * @phan-var array<int,Closure(CodeBase,Context,Node|int|string|float,array<int,Node>):void>|null
     */
    private $post_analyze_node_plugin_set;

    /**
     * @var array<int,BeforeAnalyzeFileCapability> - plugins to analyze files before Phan's analysis of that file is completed.
     */
    private $before_analyze_file_plugin_set;

    /**
     * @var array<int,BeforeAnalyzeCapability> - plugins to analyze the project before Phan starts the analyze phase.
     */
    private $before_analyze_plugin_set;

    /**
     * @var array<int,AfterAnalyzeFileCapability> - plugins to analyze files after Phan's analysis of that file is completed.
     */
    private $after_analyze_file_plugin_set;

    /** @var array<int,AnalyzeClassCapability>|null - plugins to analyze class declarations. */
    private $analyze_class_plugin_set;

    /** @var array<int,AnalyzeFunctionCallCapability>|null - plugins to analyze invocations of subsets of functions and methods. */
    private $analyze_function_call_plugin_set;

    /** @var array<int,AnalyzeFunctionCapability>|null - plugins to analyze function declarations. */
    private $analyze_function_plugin_set;

    /** @var array<int,AnalyzePropertyCapability>|null - plugins to analyze property declarations. */
    private $analyze_property_plugin_set;

    /** @var array<int,AnalyzeMethodCapability>|null - plugins to analyze method declarations.*/
    private $analyze_method_plugin_set;

    /** @var array<int,FinalizeProcessCapability>|null - plugins to call finalize() on after analysis is finished. */
    private $finalize_process_plugin_set;

    /** @var array<int,ReturnTypeOverrideCapability>|null - plugins which generate return UnionTypes of functions based on arguments. */
    private $return_type_override_plugin_set;

    /** @var array<int,SuppressionCapability>|null - plugins which generate return UnionTypes of functions based on arguments. */
    private $suppression_plugin_set;

    /** @var ?UnusedSuppressionPlugin - TODO: Refactor*/
    private $unused_suppression_plugin = null;

    /**
     * Call `ConfigPluginSet::instance()` instead.
     */
    private function __construct()
    {
    }

    /**
     * @return ConfigPluginSet
     * A shared single instance of this plugin
     */
    public static function instance() : ConfigPluginSet
    {
        static $instance = null;
        if ($instance === null) {
            $instance = self::newInstance();
        }
        return $instance;
    }

    /**
     * Returns a brand-new ConfigPluginSet where all plugins are initialized.
     *
     * If one of the plugins could not be instantiated, this prints an error message and terminates the program.
     */
    private static function newInstance() : ConfigPluginSet
    {
        try {
            $instance = new self();
            $instance->ensurePluginsExist();
            return $instance;
        } catch (Throwable $e) {
            // An unexpected error.
            // E.g. a third party plugin class threw when building the list of return types to analyze.
            $message = sprintf(
                "Failed to initialize plugins, exiting: %s: %s at %s:%d\nStack Trace:\n%s",
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            error_log($message);
            exit(EXIT_FAILURE);
        }
    }

    /**
     * Resets this set of plugins to the state it had before any user-defined or internal plugins were added,
     * then re-initialize plugins based on the current configuration.
     *
     * @internal - Used only for testing
     */
    public static function reset()
    {
        $instance = self::instance();
        // Set all of the private properties to their uninitialized default values
        // @phan-suppress-next-line PhanTypeSuspiciousNonTraversableForeach this is intentionally iterating over private properties of the clone.
        foreach (new self() as $k => $v) {
            $instance->{$k} = $v;
        }
        $instance->ensurePluginsExist();
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the node exists
     *
     * @param Context $context
     * The context in which the node exits. This is
     * the context inside the given node rather than
     * the context outside of the given node
     *
     * @param Node $node
     * The php-ast Node being analyzed.
     *
     * @return void
     */
    public function preAnalyzeNode(
        CodeBase $code_base,
        Context $context,
        Node $node
    ) {
        $plugin_callback = $this->pre_analyze_node_plugin_set[$node->kind] ?? null;
        if ($plugin_callback !== null) {
            $plugin_callback(
                $code_base,
                $context,
                $node
            );
        }
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the node exists
     *
     * @param Context $context
     * The context in which the node exits. This is
     * the context inside the given node rather than
     * the context outside of the given node
     *
     * @param Node $node
     * The php-ast Node being analyzed.
     *
     * @param array<int,Node> $parent_node_list
     * The parent node of the given node (if one exists).
     *
     * @return void
     */
    public function postAnalyzeNode(
        CodeBase $code_base,
        Context $context,
        Node $node,
        array $parent_node_list = []
    ) {
        $plugin_callback = $this->post_analyze_node_plugin_set[$node->kind] ?? null;
        if ($plugin_callback !== null) {
            $plugin_callback(
                $code_base,
                $context,
                $node,
                $parent_node_list
            );
        }
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the node exists
     *
     * @param Context $context
     * A context with the file name for $file_contents and the scope before analyzing $node.
     *
     * @param string $file_contents
     * @param Node $node
     * @return void
     * @override
     */
    public function beforeAnalyzeFile(
        CodeBase $code_base,
        Context $context,
        string $file_contents,
        Node $node
    ) {
        foreach ($this->before_analyze_file_plugin_set as $plugin) {
            $plugin->beforeAnalyzeFile(
                $code_base,
                $context,
                $file_contents,
                $node
            );
        }
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the project exists
     *
     * @override
     */
    public function beforeAnalyze(CodeBase $code_base)
    {
        foreach ($this->before_analyze_plugin_set as $plugin) {
            $plugin->beforeAnalyze($code_base);
        }
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the node exists
     *
     * @param Context $context
     * A context with the file name for $file_contents and the scope after analyzing $node.
     *
     * @param string $file_contents
     * @param Node $node
     * @return void
     * @override
     */
    public function afterAnalyzeFile(
        CodeBase $code_base,
        Context $context,
        string $file_contents,
        Node $node
    ) {
        foreach ($this->after_analyze_file_plugin_set as $plugin) {
            $plugin->afterAnalyzeFile(
                $code_base,
                $context,
                $file_contents,
                $node
            );
        }
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the class exists
     *
     * @param Clazz $class
     * A class being analyzed
     *
     * @return void
     * @override
     */
    public function analyzeClass(
        CodeBase $code_base,
        Clazz $class
    ) {
        foreach ($this->analyze_class_plugin_set as $plugin) {
            $plugin->analyzeClass(
                $code_base,
                $class
            );
        }
        if ($this->hasAnalyzePropertyPlugins()) {
            foreach ($class->getPropertyMap($code_base) as $property) {
                $this->analyzeProperty($code_base, $property);
            }
        }
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the method exists
     *
     * @param Method $method
     * A method being analyzed
     *
     * @return void
     * @override
     */
    public function analyzeMethod(
        CodeBase $code_base,
        Method $method
    ) {
        foreach ($this->analyze_method_plugin_set as $plugin) {
            $plugin->analyzeMethod(
                $code_base,
                $method
            );
        }
    }

    /**
     * This will be called if Phan's file and element-based suppressions did not suppress the issue.
     *
     * @param CodeBase $code_base
     *
     * @param Context $context context near where the issue occurred
     *
     * @param string $issue_type
     * The type of issue to emit such as Issue::ParentlessClass
     *
     * @param int $lineno
     * The line number where the issue was found
     *
     * @param array<int,string|int|float|bool|Type|UnionType|FQSEN|TypedElement|UnaddressableTypedElement> $parameters
     *
     * @param ?Suggestion $suggestion Phan's suggestion for how to fix the issue, if any.
     *
     * @return bool true if the given issue instance should be suppressed, given the current file contents.
     */
    public function shouldSuppressIssue(
        CodeBase $code_base,
        Context $context,
        string $issue_type,
        int $lineno,
        array $parameters,
        $suggestion
    ) : bool {
        foreach ($this->suppression_plugin_set as $plugin) {
            if ($plugin->shouldSuppressIssue(
                $code_base,
                $context,
                $issue_type,
                $lineno,
                $parameters,
                $suggestion
            )) {
                $unused_suppression_plugin = $this->unused_suppression_plugin;
                if ($unused_suppression_plugin) {
                    // @phan-suppress-next-line PhanAccessMethodInternal
                    $unused_suppression_plugin->recordPluginSuppression($plugin, $context->getFile(), $issue_type, $lineno);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param CodeBase $code_base
     * @param string $file_path
     * @return array<string,array<int,int>> Maps 0 or more issue types to a *list* of lines that this plugin set is going to suppress.
     */
    public function getIssueSuppressionList(
        CodeBase $code_base,
        string $file_path
    ) : array {
        $result = [];
        foreach ($this->suppression_plugin_set as $plugin) {
            $result += $plugin->getIssueSuppressionList(
                $code_base,
                $file_path
            );
        }
        return $result;
    }

    /**
     * @return array<int,SuppressionCapability>
     * @suppress PhanPossiblyNullTypeReturn should always be initialized before any issues get emitted.
     */
    public function getSuppressionPluginSet() : array
    {
        return $this->suppression_plugin_set;
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the function exists
     *
     * @param Func $function
     * A function being analyzed
     *
     * @return void
     * @override
     */
    public function analyzeFunction(
        CodeBase $code_base,
        Func $function
    ) {
        foreach ($this->analyze_function_plugin_set as $plugin) {
            $plugin->analyzeFunction(
                $code_base,
                $function
            );
        }
    }

    /**
     * @param CodeBase $code_base
     * The code base in which the property exists
     *
     * @param Property $property
     * A property being analyzed
     *
     * (Called by analyzeClass())
     *
     * @return void
     * @override
     */
    public function analyzeProperty(
        CodeBase $code_base,
        Property $property
    ) {
        foreach ($this->analyze_property_plugin_set as $plugin) {
            try {
                $plugin->analyzeProperty(
                    $code_base,
                    $property
                );
            } catch (IssueException $exception) {
                // e.g. getUnionType() can throw, PropertyTypesAnalyzer is probably emitting duplicate issues
                Issue::maybeEmitInstance(
                    $code_base,
                    $property->getContext(),
                    $exception->getIssueInstance()
                );
                continue;
            }
        }
    }

    /**
     * @param CodeBase $code_base
     * The code base used for previous analysis steps
     *
     * @return void
     * @override
     */
    public function finalizeProcess(
        CodeBase $code_base
    ) {
        foreach ($this->finalize_process_plugin_set as $plugin) {
            $plugin->finalizeProcess($code_base);
        }
    }

    /**
     * Returns true if analyzeFunction() will execute any plugins.
     */
    public function hasAnalyzeFunctionPlugins() : bool
    {
        if (is_null($this->plugin_set)) {
            throw new AssertionError("Expected plugins to be loaded in " . __METHOD__);
        }
        // @phan-suppress-next-line PhanPossiblyNullTypeArgumentInternal
        return \count($this->analyze_function_plugin_set) > 0;
    }

    /**
     * Returns true if analyzeMethod() will execute any plugins.
     */
    public function hasAnalyzeMethodPlugins() : bool
    {
        if (is_null($this->plugin_set)) {
            throw new AssertionError("Expected plugins to be loaded in " . __METHOD__);
        }
        // @phan-suppress-next-line PhanPossiblyNullTypeArgumentInternal
        return \count($this->analyze_method_plugin_set) > 0;
    }

    /**
     * @param Closure(CodeBase, Context, FunctionInterface, array):void $a
     * @param ?Closure(CodeBase, Context, FunctionInterface, array):void $b
     * @return Closure(CodeBase, Context, FunctionInterface, array):void $b
     */
    public static function mergeAnalyzeFunctionCallClosures(Closure $a, Closure $b = null)
    {
        if (!$b) {
            return $a;
        }
        return static function (CodeBase $code_base, Context $context, FunctionInterface $func, array $args) use ($a, $b) {
            $a($code_base, $context, $func, $args);
            $b($code_base, $context, $func, $args);
        };
    }
    /**
     * @param CodeBase $code_base
     * @return array<string,\Closure> maps FQSEN string to closure
     */
    public function getAnalyzeFunctionCallClosures(CodeBase $code_base) : array
    {
        if (is_null($this->plugin_set)) {
            throw new AssertionError("Expected plugins to be loaded in " . __METHOD__);
        }
        $result = [];
        foreach ($this->analyze_function_call_plugin_set as $plugin) {
            // TODO: Make this case-insensitive.
            foreach ($plugin->getAnalyzeFunctionCallClosures($code_base) as $fqsen_name => $closure) {
                $other_closure = $result[$fqsen_name] ?? null;
                $closure = self::mergeAnalyzeFunctionCallClosures($closure, $other_closure);
                $result[$fqsen_name] = $closure;
            }
        }
        return $result;
    }

    /**
     * @param CodeBase $code_base
     * @return array<string,\Closure> maps FQSEN string to closure
     */
    public function getReturnTypeOverrides(CodeBase $code_base) : array
    {
        if (is_null($this->plugin_set)) {
            throw new AssertionError("Expected plugins to be loaded in " . __METHOD__);
        }
        $result = [];
        foreach ($this->return_type_override_plugin_set as $plugin) {
            $result += $plugin->getReturnTypeOverrides($code_base);
        }
        return $result;
    }

    /** @var ?NodeSelectionPlugin - If the language server requests more information about a node, this may be set (e.g. for "Go To Definition") */
    private $node_selection_plugin;

    /**
     * @internal
     * @return void
     * @see addTemporaryAnalysisPlugin
     */
    public function prepareNodeSelectionPluginForNode(Node $node)
    {
        $node_selection_plugin = $this->node_selection_plugin;
        if (!$node_selection_plugin) {
            fwrite(STDERR, "Error: " . __METHOD__ . " called before node selection plugin was created\n");
            return;
        }

        // TODO: Track if this has been added already(not necessary yet)

        $kind = $node->kind;
        if (!\is_int($kind)) {
            throw new AssertionError("Invalid kind for node");
        }

        /**
         * @phan-closure-scope NodeSelectionVisitor
         */
        $closure = (static function (CodeBase $code_base, Context $context, Node $node, array $unused_parent_node_list = []) {
            $visitor = new NodeSelectionVisitor($code_base, $context);
            $visitor->visitCommonImplementation($node);
        });

        $this->addNodeSelectionClosureForKind($node->kind, $closure);
    }

    /**
     * @param CodeBase $code_base
     * @param ?Request $request
     * @return ?RAII
     */
    public function addTemporaryAnalysisPlugin(CodeBase $code_base, $request)
    {
        if (!$request) {
            return null;
        }
        $node_info_request = $request->getMostRecentNodeInfoRequest();
        if (!$node_info_request) {
            return null;
        }
        $node_selection_plugin = new NodeSelectionPlugin();
        if ($node_info_request instanceof GoToDefinitionRequest) {
            $node_selection_plugin->setNodeSelectorClosure(DefinitionResolver::createGoToDefinitionClosure($node_info_request, $code_base));
        } elseif ($node_info_request instanceof CompletionRequest) {
            $node_selection_plugin->setNodeSelectorClosure(CompletionResolver::createCompletionClosure($node_info_request, $code_base));
        } else {
            throw new AssertionError("Unknown subclass of NodeInfoRequest - Should not happen");
        }
        $this->node_selection_plugin = $node_selection_plugin;

        $old_post_analyze_node_plugin_set = $this->post_analyze_node_plugin_set;

        /*
        $new_post_analyze_node_plugins = self::filterPostAnalysisPlugins([$node_selection_plugin]);
        if (!$new_post_analyze_node_plugins) {
            throw new \RuntimeException("Invalid NodeSelectionPlugin");
        }

        // TODO: This can be removed?
        foreach ($new_post_analyze_node_plugins as $kind => $new_plugin) {
            $this->addNodeSelectionClosureForKind($kind, $new_plugin);
        }
         */

        return new RAII(function () use ($old_post_analyze_node_plugin_set) {
            $this->post_analyze_node_plugin_set = $old_post_analyze_node_plugin_set;
            $this->node_selection_plugin = null;
        });
    }

    /**
     * @param Closure(CodeBase,Context,Node,array=) $new_plugin
     */
    private function addNodeSelectionClosureForKind(int $kind, Closure $new_plugin)
    {
        $old_plugin_for_kind = $this->post_analyze_node_plugin_set[$kind] ?? null;
        if ($old_plugin_for_kind) {
            /**
             * @suppress PhanInfiniteRecursion the old plugin is referring to a different closure
             */
            $this->post_analyze_node_plugin_set[$kind] = static function (CodeBase $code_base, Context $context, Node $node, array $parent_node_list = []) use ($old_plugin_for_kind, $new_plugin) {
                $old_plugin_for_kind($code_base, $context, $node, $parent_node_list);
                $new_plugin($code_base, $context, $node, $parent_node_list);
            };
        } else {
            $this->post_analyze_node_plugin_set[$kind] = $new_plugin;
        }
    }

    /**
     * Returns true if analyzeProperty() will execute any plugins.
     */
    private function hasAnalyzePropertyPlugins() : bool
    {
        if (is_null($this->plugin_set)) {
            throw new AssertionError("Expected plugins to be loaded in " . __METHOD__);
        }
        // @phan-suppress-next-line PhanPossiblyNullTypeArgumentInternal
        return \count($this->analyze_property_plugin_set) > 0;
    }

    /**
     * @return void
     */
    private function ensurePluginsExist()
    {
        if (!is_null($this->plugin_set)) {
            return;
        }
        // Add user-defined plugins.
        $plugin_set = array_map(
            function (string $plugin_file_name) : PluginV2 {
                // Allow any word/UTF-8 identifier as a php file name.
                // E.g. 'AlwaysReturnPlugin' becomes /path/to/phan/.phan/plugins/AlwaysReturnPlugin.php
                // (Useful when using phan.phar, etc.)
                if (\preg_match('@^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$@', $plugin_file_name) > 0) {
                    $plugin_file_name = __DIR__ . '/../../../.phan/plugins/' . $plugin_file_name . '.php';
                }

                try {
                    $plugin_instance = require($plugin_file_name);
                } catch (Throwable $e) {
                    // An unexpected error.
                    // E.g. a plugin class threw a SyntaxError because it required PHP 7.1 or newer but 7.0 was used.
                    $message = sprintf(
                        "Failed to initialize plugin %s, exiting: %s: %s at %s:%d\nStack Trace:\n%s",
                        $plugin_file_name,
                        get_class($e),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    );
                    error_log($message);
                    exit(EXIT_FAILURE);
                }

                if (!is_object($plugin_instance)) {
                    throw new AssertionError("Plugins must return an instance of the plugin. The plugin at $plugin_file_name does not.");
                }

                if (!($plugin_instance instanceof PluginV2)) {
                    throw new AssertionError("Plugins must extend \Phan\PluginV2. The plugin at $plugin_file_name does not.");
                }

                return $plugin_instance;
            },
            Config::getValue('plugins')
        );
        // Add internal plugins. Can be disabled by disable_internal_return_type_plugins.
        if (Config::getValue('enable_internal_return_type_plugins')) {
            $internal_return_type_plugins = [
                new ArrayReturnTypeOverridePlugin(),
                new CallableParamPlugin(),
                new CompactPlugin(),
                new ClosureReturnTypeOverridePlugin(),
                new DependentReturnTypeOverridePlugin(),
                new StringFunctionPlugin(),
                new MiscParamPlugin(),
            ];
            $plugin_set = array_merge($internal_return_type_plugins, $plugin_set);
        }
        if (Config::getValue('enable_include_path_checks')) {
            $plugin_set[] = new RequireExistsPlugin();
        }
        if (Config::getValue('warn_about_undocumented_throw_statements')) {
            $plugin_set[] = new ThrowAnalyzerPlugin();
        }
        if (Config::getValue('unused_variable_detection') || Config::getValue('dead_code_detection')) {
            $plugin_set[] = new VariableTrackerPlugin();
        }
        if (self::requiresPluginBasedBuiltinSuppressions()) {
            if (\function_exists('token_get_all')) {
                $plugin_set[] = new BuiltinSuppressionPlugin();
            } else {
                fwrite(STDERR, "ext-tokenizer is required for file-based and line-based suppressions to work, as well as the error-tolerant parser fallback." . PHP_EOL);
                fwrite(STDERR, "(This warning can be disabled by setting skip_missing_tokenizer_warning in the project's config)" . PHP_EOL);
            }
        }

        // Register the entire set.
        $this->plugin_set = $plugin_set;

        $this->pre_analyze_node_plugin_set      = self::filterPreAnalysisPlugins($plugin_set);
        $this->post_analyze_node_plugin_set     = self::filterPostAnalysisPlugins($plugin_set);
        $this->before_analyze_plugin_set        = self::filterByClass($plugin_set, BeforeAnalyzeCapability::class);
        $this->before_analyze_file_plugin_set   = self::filterByClass($plugin_set, BeforeAnalyzeFileCapability::class);
        $this->after_analyze_file_plugin_set    = self::filterByClass($plugin_set, AfterAnalyzeFileCapability::class);
        $this->analyze_method_plugin_set        = self::filterByClass($plugin_set, AnalyzeMethodCapability::class);
        $this->analyze_function_plugin_set      = self::filterByClass($plugin_set, AnalyzeFunctionCapability::class);
        $this->analyze_property_plugin_set      = self::filterByClass($plugin_set, AnalyzePropertyCapability::class);
        $this->analyze_class_plugin_set         = self::filterByClass($plugin_set, AnalyzeClassCapability::class);
        $this->finalize_process_plugin_set      = self::filterByClass($plugin_set, FinalizeProcessCapability::class);
        $this->return_type_override_plugin_set  = self::filterByClass($plugin_set, ReturnTypeOverrideCapability::class);
        $this->suppression_plugin_set           = self::filterByClass($plugin_set, SuppressionCapability::class);
        $this->analyze_function_call_plugin_set = self::filterByClass($plugin_set, AnalyzeFunctionCallCapability::class);
        $this->unused_suppression_plugin        = self::findUnusedSuppressionPlugin($plugin_set);
    }

    private static function requiresPluginBasedBuiltinSuppressions() : bool
    {
        if (Config::getValue('disable_suppression')) {
            return false;
        }
        if (Config::getValue('disable_line_based_suppression') && Config::getValue('disable_file_based_suppression')) {
            return false;
        }
        return true;
    }

    /**
     * @return array<int,Closure>
     *         Returned value maps ast\Node->kind to [function(CodeBase $code_base, Context $context, Node $node, array<int,Node> $parent_node_list = []): void]
     * @phan-return array<int,Closure(CodeBase,Context,Node,array<int,Node>=):void>
     */
    private static function filterPreAnalysisPlugins(array $plugin_set) : array
    {
        $closures_for_kind = new ClosuresForKind();
        foreach ($plugin_set as $plugin) {
            if ($plugin instanceof PreAnalyzeNodeCapability) {
                self::addClosuresForPreAnalyzeNodeCapability($closures_for_kind, $plugin);
            }
        }
        /**
         * @param array<int,Closure> $closure_list
         */
        return $closures_for_kind->getFlattenedClosures(static function (array $closure_list) : \Closure {
            return static function (CodeBase $code_base, Context $context, Node $node) use ($closure_list) {
                foreach ($closure_list as $closure) {
                    $closure($code_base, $context, $node);
                }
            };
        });
    }

    private static function addClosuresForPreAnalyzeNodeCapability(
        ClosuresForKind $closures_for_kind,
        PreAnalyzeNodeCapability $plugin
    ) {
        $plugin_analysis_class = $plugin->getPreAnalyzeNodeVisitorClassName();
        if (!\is_subclass_of($plugin_analysis_class, PluginAwarePreAnalysisVisitor::class)) {
            throw new \TypeError(
                sprintf(
                    "Result of %s::getAnalyzeNodeVisitorClassName must be the name of a subclass of '%s', but '%s' is not",
                    \get_class($plugin),
                    PluginAwarePreAnalysisVisitor::class,
                    $plugin_analysis_class
                )
            );
        }
        // @see PreAnalyzeNodeCapability (magic to create parent_node_list)
        $closure = self::getGenericClosureForPluginAwarePreAnalysisVisitor($plugin_analysis_class);
        $handled_node_kinds = $plugin_analysis_class::getHandledNodeKinds();
        if (\count($handled_node_kinds) === 0) {
            fprintf(
                STDERR,
                "Plugin %s has a preAnalyzeNode visitor %s (subclass of %s) which doesn't override any known visit<Suffix>() methods, but expected at least one method to be overridden\n",
                \get_class($plugin),
                $plugin_analysis_class,
                PluginAwarePreAnalysisVisitor::class
            );
        }
        $closures_for_kind->recordForKinds($handled_node_kinds, $closure);
    }

    /**
     * Create an instance of $plugin_analysis_class and run the visit*() method corresponding to $node->kind.
     *
     * @return Closure(CodeBase,Context,Node,array=)
     */
    private static function getGenericClosureForPluginAwarePreAnalysisVisitor(string $plugin_analysis_class) : Closure
    {
        try {
            new ReflectionProperty($plugin_analysis_class, 'parent_node_list');
            $has_parent_node_list = true;
        } catch (ReflectionException $_) {
            $has_parent_node_list = false;
        }

        if ($has_parent_node_list) {
            /**
             * Create an instance of $plugin_analysis_class and run the visit*() method corresponding to $node->kind.
             *
             * @phan-closure-scope PluginAwarePreAnalysisVisitor
             */
            return (static function (CodeBase $code_base, Context $context, Node $node, array $parent_node_list = []) {
                $visitor = new static($code_base, $context);
                // @phan-suppress-next-line PhanUndeclaredProperty checked via $has_parent_node_list
                $visitor->parent_node_list = $parent_node_list;
                $fn_name = Element::VISIT_LOOKUP_TABLE[$node->kind];
                $visitor->{$fn_name}($node);
            })->bindTo(null, $plugin_analysis_class);
        } else {
            /**
             * Create an instance of $plugin_analysis_class and run the visit*() method corresponding to $node->kind.
             *
             * @phan-closure-scope PluginAwarePreAnalysisVisitor
             */
            return (static function (CodeBase $code_base, Context $context, Node $node, array $unused_parent_node_list = []) {
                $visitor = new static($code_base, $context);
                $fn_name = Element::VISIT_LOOKUP_TABLE[$node->kind];
                $visitor->{$fn_name}($node);
            })->bindTo(null, $plugin_analysis_class);
        }
    }


    /**
     * @return array<int,\Closure> - [function(CodeBase $code_base, Context $context, Node $node, array<int,Node> $parent_node_list = []): void]
     */
    private static function filterPostAnalysisPlugins(array $plugin_set) : array
    {
        $closures_for_kind = new ClosuresForKind();
        foreach ($plugin_set as $plugin) {
            if ($plugin instanceof PostAnalyzeNodeCapability) {
                self::addClosuresForPostAnalyzeNodeCapability($closures_for_kind, $plugin);
            }
        }
        /**
         * @param array<int,Closure> $closure_list
         */
        return $closures_for_kind->getFlattenedClosures(static function (array $closure_list) : \Closure {
            return static function (CodeBase $code_base, Context $context, Node $node, array $parent_node_list = []) use ($closure_list) {
                foreach ($closure_list as $closure) {
                    $closure($code_base, $context, $node, $parent_node_list);
                }
            };
        });
    }

    /**
     * @throws \TypeError if the returned getPostAnalyzeNodeVisitorClassName() is invalid
     */
    private static function addClosuresForPostAnalyzeNodeCapability(
        ClosuresForKind $closures_for_kind,
        PostAnalyzeNodeCapability $plugin
    ) {
        $plugin_analysis_class = $plugin->getPostAnalyzeNodeVisitorClassName();
        if (!\is_subclass_of($plugin_analysis_class, PluginAwarePostAnalysisVisitor::class)) {
            throw new \TypeError(
                sprintf(
                    "Result of %s::getAnalyzeNodeVisitorClassName must be the name of a subclass of '%s', but '%s' is not",
                    \get_class($plugin),
                    PluginAwarePostAnalysisVisitor::class,
                    $plugin_analysis_class
                )
            );
        }

        // @see PostAnalyzeNodeCapability (magic to create parent_node_list)
        $closure = self::getGenericClosureForPluginAwarePostAnalysisVisitor($plugin_analysis_class);

        $handled_node_kinds = $plugin_analysis_class::getHandledNodeKinds();
        if (\count($handled_node_kinds) === 0) {
            fprintf(
                STDERR,
                "Plugin %s has an analyzeNode visitor %s (subclass of %s) which doesn't override any known visit<Suffix>() methods, but expected at least one method to be overridden\n",
                \get_class($plugin),
                $plugin_analysis_class,
                PluginAwarePostAnalysisVisitor::class
            );
        }
        $closures_for_kind->recordForKinds($handled_node_kinds, $closure);
    }

    /**
     * Create an instance of $plugin_analysis_class and run the visit*() method corresponding to $node->kind.
     *
     * @return Closure(CodeBase,Context,Node,array=)
     */
    private static function getGenericClosureForPluginAwarePostAnalysisVisitor(string $plugin_analysis_class) : Closure
    {
        try {
            new ReflectionProperty($plugin_analysis_class, 'parent_node_list');
            $has_parent_node_list = true;
        } catch (ReflectionException $_) {
            $has_parent_node_list = false;
        }

        if ($has_parent_node_list) {
            /**
             * Create an instance of $plugin_analysis_class and run the visit*() method corresponding to $node->kind.
             *
             * @phan-closure-scope PluginAwarePostAnalysisVisitor
             */
            return (static function (CodeBase $code_base, Context $context, Node $node, array $parent_node_list = []) {
                $visitor = new static($code_base, $context);
                // @phan-suppress-next-line PhanUndeclaredProperty checked via $has_parent_node_list
                $visitor->parent_node_list = $parent_node_list;
                $fn_name = Element::VISIT_LOOKUP_TABLE[$node->kind];
                $visitor->{$fn_name}($node);
            })->bindTo(null, $plugin_analysis_class);
        } else {
            /**
             * Create an instance of $plugin_analysis_class and run the visit*() method corresponding to $node->kind.
             *
             * @phan-closure-scope PluginAwarePostAnalysisVisitor
             */
            return (static function (CodeBase $code_base, Context $context, Node $node, array $unused_parent_node_list = []) {
                $visitor = new static($code_base, $context);
                $fn_name = Element::VISIT_LOOKUP_TABLE[$node->kind];
                $visitor->{$fn_name}($node);
            })->bindTo(null, $plugin_analysis_class);
        }
    }

    private static function filterByClass(array $plugin_set, string $interface_name) : array
    {
        $result = [];
        foreach ($plugin_set as $plugin) {
            if ($plugin instanceof $interface_name) {
                $result[] = $plugin;
            }
        }
        return $result;
    }

    /**
     * @param PluginV2[] $plugin_set
     * @return ?UnusedSuppressionPlugin
     */
    private static function findUnusedSuppressionPlugin(array $plugin_set)
    {
        foreach ($plugin_set as $plugin) {
            // Don't use instanceof, avoid triggering class autoloader unnecessarily.
            // (load one less file)
            if (\get_class($plugin) === 'UnusedSuppressionPlugin') {
                return $plugin;
            }
        }
        return null;
    }
}
