<?php declare(strict_types=1);

namespace Phan;

use Phan\Language\FQSEN;
use Phan\Language\Type;
use Phan\Language\UnionType;
use Phan\Output\Colorizing;

/**
 * Represents an instance of an issue at a given file and line for the given template parameters.
 *
 * Some IssueInstances are created with suggestions for how they can be fixed
 *
 * @see Issue for how these are emitted. Visitors and plugins often have helper methods to emit issues.
 * @see OutputPrinter for how this is converted to various output formats
 * @phan-file-suppress PhanPluginDescriptionlessCommentOnPublicMethod
 */
class IssueInstance
{
    /** @var Issue the generic category of issues */
    private $issue;

    /** @var string the file in which this issue was emitted. */
    private $file;

    /** @var int the line in which this issue was emitted. */
    private $line;

    /** @var string the formatted issue message */
    private $message;

    /** @var ?Suggestion If this is non-null, this contains suggestions on how to resolve the error. */
    private $suggestion;

    /** @var array<int,string|int|float> $template_parameters If this is non-null, this contains suggestions on how to resolve the error. */
    private $template_parameters;

    /**
     * @param Issue $issue
     * @param string $file
     * @param int $line
     * @param array<int,string|int|float|FQSEN|Type|UnionType> $template_parameters
     * @param ?Suggestion $suggestion
     */
    public function __construct(
        Issue $issue,
        string $file,
        int $line,
        array $template_parameters,
        Suggestion $suggestion = null
    ) {
        $this->issue = $issue;
        $this->file = $file;
        $this->line = $line;
        $this->suggestion = $suggestion;

        // color_issue_message will interfere with some formatters, such as xml.
        if (Config::getValue('color_issue_messages')) {
            $this->message = self::generateColorizedMessage($issue, $template_parameters);
        } else {
            $this->message = self::generatePlainMessage($issue, $template_parameters);
        }
        // Fixes #1754 : Some issue template parameters might not be serializable (for passing to ForkPool)

        /**
         * @param string|float|int|FQSEN|Type|UnionType $parameter
         * @return string|float|int
         */
        $this->template_parameters = \array_map(function ($parameter) {
            if (\is_object($parameter)) {
                return (string)$parameter;
            }
            return $parameter;
        }, $template_parameters);
    }

    /**
     * @param array<int,string|int|float|bool|object> $template_parameters
     */
    private static function generatePlainMessage(
        Issue $issue,
        array $template_parameters
    ) : string {
        $template = $issue->getTemplate();

        // markdown_issue_messages doesn't make sense with color, unless you add <span style="color:red">msg</span>
        // Not sure if codeclimate supports that.
        if (Config::getValue('markdown_issue_messages')) {
            $template = preg_replace(
                '/([^ ]*%s[^ ]*)/',
                '`\1`',
                $template
            );
        }
        return vsprintf(
            $template,
            $template_parameters
        );
    }

    /**
     * @param array<int,string|int|float|FQSEN|Type|UnionType> $template_parameters
     */
    private static function generateColorizedMessage(
        Issue $issue,
        array $template_parameters,
        string $suggestion = null
    ) : string {
        $template = $issue->getTemplateRaw();

        $result = Colorizing::colorizeTemplate($template, $template_parameters);
        if ($suggestion) {
            $result .= Colorizing::colorizeTemplate(" ({SUGGESTION})", [$suggestion]);
        }
        return $result;
    }

    /**
     * @return ?Suggestion
     */
    public function getSuggestion()
    {
        return $this->suggestion;
    }

    /** @var array<int,string|int|float|FQSEN|Type|UnionType> $template_parameters If this is non-null, this contains suggestions on how to resolve the error. */
    public function getTemplateParameters() : array
    {
        return $this->template_parameters;
    }

    /** @return ?string */
    public function getSuggestionMessage()
    {
        $suggestion = $this->suggestion;
        if (!$suggestion) {
            return null;
        }
        return $suggestion->getMessage() ?: null;
    }

    /**
     * @return Issue
     */
    public function getIssue() : Issue
    {
        return $this->issue;
    }

    /**
     * @return string
     */
    public function getFile() : string
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getLine() : int
    {
        return $this->line;
    }

    /**
     * @return string
     */
    public function getMessage() : string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getMessageAndMaybeSuggestion() : string
    {
        $message = $this->getMessage();
        $suggestion = $this->getSuggestionMessage();
        if ($suggestion) {
            return $message . ' (' . $suggestion . ')';
        }
        return $message;
    }

    public function __toString() : string
    {
        return "{$this->getFile()}:{$this->getLine()} {$this->getMessageAndMaybeSuggestion()}";
    }
}
