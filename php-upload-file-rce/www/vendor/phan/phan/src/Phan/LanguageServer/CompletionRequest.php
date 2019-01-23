<?php declare(strict_types=1);

namespace Phan\LanguageServer;

use Exception;
use Phan\CodeBase;
use Phan\Config;
use Phan\Language\Element\ClassConstant;
use Phan\Language\Element\Clazz;
use Phan\Language\Element\Func;
use Phan\Language\Element\GlobalConstant;
use Phan\Language\Element\Method;
use Phan\Language\Element\Property;
use Phan\Language\Element\TypedElementInterface;
use Phan\Language\Element\Variable;
use Phan\LanguageServer\Protocol\CompletionContext;
use Phan\LanguageServer\Protocol\CompletionItem;
use Phan\LanguageServer\Protocol\CompletionItemKind;
use Phan\LanguageServer\Protocol\CompletionList;
use Phan\LanguageServer\Protocol\Position;

/**
 * Represents the Language Server Protocol's "Completion" request for an element
 * (property, method, class constant, etc.)
 *
 * @see https://microsoft.github.io/language-server-protocol/specification#textDocument_completion
 *
 * @see CompletionResolver for how this maps the found node to the type in the context.
 * @see \Phan\Plugin\Internal\NodeSelectionPlugin for how the node is found
 * @see \Phan\AST\TolerantASTConverter\TolerantASTConverterWithNodeMapping for how isSelected is set
 *
 * @phan-file-suppress PhanPluginDescriptionlessCommentOnPublicMethod
 */
final class CompletionRequest extends NodeInfoRequest
{
    /**
     * @var array<string,CompletionItem> the list of completion items.
     */
    private $completions = [];

    /**
     * Construct a CompletionRequest from the parameters provided by the language server client.
     *
     * @suppress PhanUnusedPublicFinalMethodParameter
     */
    public function __construct(
        string $uri,
        Position $position,
        CompletionContext $completion_context = null
    ) {
        parent::__construct($uri, $position);
    }

    /**
     * @param ?CompletionItem|?array<int,CompletionItem> $completions
     * @return void
     */
    public function recordCompletionList($completions)
    {
        if ($completions instanceof CompletionItem || isset($completions['label'])) {
            $completions = [$completions];
        }
        foreach ($completions ?? [] as $completion) {
            if (is_array($completion)) {
                $completion = CompletionItem::fromArray($completion);
            }
            $this->recordCompletionItem($completion);
        }
    }

    /**
     * Records the definition of an element that can be used for a code completion
     *
     * @param CodeBase $code_base used for resolving type location in "Completion"
     * @param ClassConstant|Clazz|Func|GlobalConstant|Method|Property|Variable $element
     * @return void
     */
    public function recordCompletionElement(
        CodeBase $code_base,
        TypedElementInterface $element,
        string $prefix = null
    ) {
        $item = $this->createCompletionItem($code_base, $element, $prefix);
        $this->recordCompletionItem($item);
    }

    private function recordCompletionItem(CompletionItem $item)
    {
        $this->completions[$item->label . ':' . $item->kind] = $item;
    }

    private function createCompletionItem(
        CodeBase $unused_code_base,
        TypedElementInterface $element,
        string $prefix = null
    ) : CompletionItem {
        $item = new CompletionItem();
        $item->label = $this->labelForElement($element);
        $item->kind = $this->kindForElement($element);
        $item->detail = (string)$element->getUnionType() ?: 'mixed';  // TODO: Better summary
        $item->documentation = null;  // TODO: Better summary, use phpdoc summary

        $insert_text = null;
        if (!self::useVSCodeCompletion()) {
            if ($element instanceof Property && $element->isStatic()) {
                $insert_text = '$' . $element->getName();
            }
            if (is_string($prefix) && is_string($insert_text) && strncmp($insert_text, $prefix, strlen($prefix)) === 0) {
                $insert_text = (string)substr($insert_text, strlen($prefix));
            }
        }
        $item->insertText = $insert_text;

        return $item;
    }

    /**
     * If true, then return completion suggestions that are compatible with VS Code.
     */
    public static function useVSCodeCompletion() : bool
    {
        return Config::COMPLETION_VSCODE === Config::getValue('language_server_enable_completion');
    }

    private function labelForElement(TypedElementInterface $element) : string
    {
        if (self::useVSCodeCompletion()) {
            $name = $element->getName();
            if ($element instanceof Variable) {
                return '$' . $name;
            }
            if ($element instanceof Property && $element->isStatic()) {
                return '$' . $name;
            }
            return $name;
        }
        return $element->getName();
    }

    /**
     * @return ?int
     */
    private function kindForElement(TypedElementInterface $element)
    {
        if ($element instanceof ClassConstant) {
            return CompletionItemKind::VARIABLE;
        } elseif ($element instanceof Clazz) {
            return CompletionItemKind::CLASS_;
        } elseif ($element instanceof Func) {
            return CompletionItemKind::FUNCTION;
        } elseif ($element instanceof GlobalConstant) {
            return CompletionItemKind::VARIABLE;
        } elseif ($element instanceof Method) {
            return CompletionItemKind::METHOD;
        } elseif ($element instanceof Property) {
            return CompletionItemKind::PROPERTY;
        } elseif ($element instanceof Variable) {
            return CompletionItemKind::VARIABLE;
        }
        // TODO: Implement
        return null;
    }

    /**
     * @return array<int,CompletionItem>
     */
    public function getCompletions() : array
    {
        return array_values($this->completions);
    }

    public function finalize()
    {
        $promise = $this->promise;
        if ($promise) {
            $result = $this->completions ?: null;
            if ($result !== null) {
                // Sort completion suggestions alphabetically,
                // ignoring the leading `$` in variables/static properties.
                uksort(
                    $result,
                    /**
                     * @param string $a
                     * @param string $b
                     */
                    function ($a, $b) : int {
                        $a = ltrim((string)$a, '$');
                        $b = ltrim((string)$b, '$');
                        return (strtolower($a) <=> strtolower($b)) ?: ($a <=> $b);
                    }
                );
                $result_list = new CompletionList(array_values($result));
            } else {
                $result_list = null;
            }
            $promise->fulfill($result_list);
            $this->promise = null;
        }
    }

    public function __destruct()
    {
        $promise = $this->promise;
        if ($promise) {
            $promise->reject(new Exception('Failed to send a valid textDocument/definition result'));
            $this->promise = null;
        }
    }
}
