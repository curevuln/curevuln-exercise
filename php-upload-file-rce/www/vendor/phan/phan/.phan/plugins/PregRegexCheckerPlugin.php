<?php declare(strict_types=1);

use ast\Node;
use Phan\AST\ContextNode;
use Phan\AST\UnionTypeVisitor;
use Phan\CodeBase;
use Phan\Language\Context;
use Phan\Language\Element\Func;
use Phan\Language\Type\IterableType;
use Phan\Language\Type\LiteralStringType;
use Phan\Library\RegexKeyExtractor;
use Phan\Library\StringUtil;
use Phan\PluginV2;
use Phan\PluginV2\AnalyzeFunctionCallCapability;

/**
 * This plugin checks for invalid regexes in calls to preg_match. (And all of the other internal PCRE functions).
 *
 * This plugin performs this check by attempting to match the empty string,
 * then checking if PHP emitted a warning (Instead of failing to match)
 * (PHP doesn't have preg_validate())
 *
 * - getAnalyzeFunctionCallClosures
 *   This method returns a map from function/method FQSEN to closures that are called on invocations of those closures.
 */
class PregRegexCheckerPlugin extends PluginV2 implements AnalyzeFunctionCallCapability
{
    // Skip over analyzing regex keys that couldn't be resolved.
    // Don't try to convert values to PHP data (should be closures)
    const RESOLVE_REGEX_KEY_FLAGS = (ContextNode::RESOLVE_DEFAULT | ContextNode::RESOLVE_KEYS_SKIP_UNKNOWN_KEYS) &
        ~(ContextNode::RESOLVE_KEYS_SKIP_UNKNOWN_KEYS | ContextNode::RESOLVE_ARRAY_VALUES);


    /** @return void */
    private function analyzePattern(CodeBase $code_base, Context $context, Func $function, string $pattern)
    {
        /**
         * @suppress PhanParamSuspiciousOrder 100% deliberate use of varying regex and constant $subject for preg_match
         * @return ?array
         */
        $err = with_disabled_phan_error_handler(function () use ($pattern) {
            $old_error_reporting = error_reporting();
            \error_reporting(0);
            \ob_start();
            \error_clear_last();
            try {
                // Annoyingly, preg_match would not warn about the `/e` modifier, removed in php 7.
                // Use `preg_replace` instead (The eval body is empty and phan requires 7.0+ to run)
                $result = @\preg_replace($pattern, '', '');
                if ($result === false || $result === null) {
                    return \error_get_last() ?? [];
                }
                return null;
            } finally {
                \ob_end_clean();
                \error_reporting($old_error_reporting);
            }
        });
        if ($err !== null) {
            // TODO: scan for 'at offset %d$' and print the corresponding section of the regex. Note: Have to remove delimiters and unescape characters within the delimiters.
            $this->emitIssue(
                $code_base,
                $context,
                'PhanPluginInvalidPregRegex',
                'Call to {FUNCTION} was passed an invalid regex {STRING_LITERAL}: {DETAILS}',
                [(string)$function->getFQSEN(), StringUtil::encodeValue($pattern), \preg_replace('@^preg_replace\(\): @', '', $err['message'] ?? 'unknown error')]
            );
            return;
        }
    }

    /**
     * @param CodeBase $code_base
     * @param Context $context
     * @param Node|string|int|float $pattern
     * @return array<string,string>
     */
    private function extractStringsFromStringOrArray(
        CodeBase $code_base,
        Context $context,
        $pattern
    ) : array {
        if (\is_string($pattern)) {
            return [$pattern => $pattern];
        }
        $pattern_union_type = UnionTypeVisitor::unionTypeFromNode($code_base, $context, $pattern);
        $result = [];
        foreach ($pattern_union_type->getTypeSet() as $type) {
            if ($type instanceof LiteralStringType) {
                $value = $type->getValue();
                $result[$value] = $value;
            } elseif ($type instanceof IterableType) {
                $iterable_type = $type->iterableValueUnionType($code_base);
                foreach ($iterable_type ? $iterable_type->getTypeSet() : [] as $element_type) {
                    if ($element_type instanceof LiteralStringType) {
                        $value = $element_type->getValue();
                        $result[$value] = $value;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param array<int,string> $patterns 1 or more regex patterns
     * @throws InvalidArgumentException if any regex could not be parsed by the heuristics
     */
    private function computePatternKeys(array $patterns) : array
    {
        $result = [];
        foreach ($patterns as $regex) {
            $result += RegexKeyExtractor::getKeys($regex);
        }
        return $result;
    }

    /**
     * @return array<int|string,string> references to indices in the pattern
     */
    private function extractTemplateKeys(string $template) : array
    {
        $result = [];
        // > replacement may contain references of the form \\n or $n,
        // ...
        // > n can be from 0 to 99, and \\0 or $0 refers to the text matched by the whole pattern.
        preg_match_all('/[$\\\\]([0-9]{1,2}|[^0-9{]|(?<=\$)\{[0-9]{1,2}\})/', $template, $all_matches, PREG_SET_ORDER);
        foreach ($all_matches as $match) {
            $key = $match[1];
            if ($key[0] === '{') {
                $key = (string)\substr($key, 1, -1);
            }
            if ($key[0] >= '0' && $key[0] <= '9') {
                // Edge case: Convert '09' to 9
                $result[(int)$key] = $match[0];
            }
        }
        return $result;
    }

    /**
     * @param string[] $patterns 1 or more regex patterns
     * @param Node|string|int|float $replacement_node
     */
    private function analyzeReplacementTemplate(CodeBase $code_base, Context $context, array $patterns, $replacement_node)
    {
        $replacement_templates = $this->extractStringsFromStringOrArray($code_base, $context, $replacement_node);
        $pattern_keys = null;

        // https://secure.php.net/manual/en/function.preg-replace.php#refsect1-function.preg-replace-parameters
        // > $replacement may contain references of the form \\n or $n, with the latter form being the preferred one.
        try {
            foreach ($replacement_templates as $replacement_template) {
                $pattern_keys = $pattern_keys ?? $this->computePatternKeys($patterns);
                $regex_group_keys = $this->extractTemplateKeys($replacement_template);
                foreach ($regex_group_keys as $key => $reference_string) {
                    if (!isset($pattern_keys[$key])) {
                        usort($patterns, 'strcmp');
                        $this->emitIssue(
                            $code_base,
                            $context,
                            'PhanPluginInvalidPregRegexReplacement',
                            'Call to {FUNCTION} was passed an invalid replacement reference {STRING_LITERAL} to pattern {STRING_LITERAL}',
                            ['\preg_replace', StringUtil::encodeValue($reference_string), StringUtil::encodeValueList(' or ', $patterns)]
                        );
                    }
                }
            }
        } catch (InvalidArgumentException $_) {
            // TODO: Is this warned about elsewhere?
            return;
        }
    }

    /**
     * @param CodeBase $code_base @phan-unused-param
     * @return array<string, Closure(CodeBase,Context,Func,array):void>
     */
    public function getAnalyzeFunctionCallClosures(CodeBase $code_base) : array
    {
        /**
         * @return void
         */
        $preg_pattern_callback = function (
            CodeBase $code_base,
            Context $context,
            Func $function,
            array $args
        ) {
            if (count($args) < 1) {
                return;
            }
            $pattern = $args[0];
            if ($pattern instanceof Node) {
                $pattern = (new ContextNode($code_base, $context, $pattern))->getEquivalentPHPScalarValue();
            }
            if (\is_string($pattern)) {
                $this->analyzePattern($code_base, $context, $function, $pattern);
            }
        };

        /**
         * @param array<int,Node|int|string|float> $args
         * @return void
         */
        $preg_pattern_or_array_callback = function (
            CodeBase $code_base,
            Context $context,
            Func $function,
            array $args
        ) {
            if (count($args) < 1) {
                return;
            }
            $pattern_node = $args[0];
            foreach ($this->extractStringsFromStringOrArray($code_base, $context, $pattern_node) as $pattern) {
                $this->analyzePattern($code_base, $context, $function, $pattern);
            }
        };

        /**
         * @param array<int,Node|int|string|float> $args
         * @return void
         */
        $preg_pattern_and_replacement_callback = function (
            CodeBase $code_base,
            Context $context,
            Func $function,
            array $args
        ) {
            if (count($args) < 1) {
                return;
            }
            $pattern_node = $args[0];
            $patterns = $this->extractStringsFromStringOrArray($code_base, $context, $pattern_node);
            if (count($patterns) === 0) {
                return;
            }
            foreach ($patterns as $pattern) {
                $this->analyzePattern($code_base, $context, $function, $pattern);
            }
            if (count($args) < 2) {
                return;
            }
            $this->analyzeReplacementTemplate($code_base, $context, $patterns, $args[1]);
        };

        /**
         * @return void
         */
        $preg_replace_callback_array_callback = function (
            CodeBase $code_base,
            Context $context,
            Func $function,
            array $args
        ) {
            if (count($args) < 1) {
                return;
            }
            // TODO: Resolve global constants and class constants?
            $pattern = $args[0];
            if ($pattern instanceof Node) {
                $pattern = (new ContextNode($code_base, $context, $pattern))->getEquivalentPHPValue(self::RESOLVE_REGEX_KEY_FLAGS);
            }
            if (\is_array($pattern)) {
                foreach ($pattern as $child_pattern => $_) {
                    if (\is_scalar($child_pattern)) {
                        $this->analyzePattern($code_base, $context, $function, (string)$child_pattern);
                    }
                }
                return;
            }
        };

        // TODO: Check that the callbacks have the right signatures in another PR?
        return [
            // call
            'preg_filter'                 => $preg_pattern_or_array_callback,
            'preg_grep'                   => $preg_pattern_callback,
            'preg_match'                  => $preg_pattern_callback,
            'preg_match_all'              => $preg_pattern_callback,
            'preg_replace_callback_array' => $preg_replace_callback_array_callback,
            'preg_replace_callback'       => $preg_pattern_or_array_callback,
            'preg_replace'                => $preg_pattern_and_replacement_callback,
            'preg_split'                  => $preg_pattern_callback,
        ];
    }
}

// Every plugin needs to return an instance of itself at the
// end of the file in which it's defined.
return new PregRegexCheckerPlugin();
