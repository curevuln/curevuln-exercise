<?php declare(strict_types=1);

namespace Phan\Analysis\ConditionVisitor;

use ast\Node;
use Phan\Analysis\ConditionVisitorInterface;
use Phan\Language\Context;

/**
 * This represents a relative comparison assertion implementation acting on two sides of a condition (<, <=, >, >=)
 */
class ComparisonCondition implements BinaryCondition
{
    /** @var int the value of ast\Node->flags */
    private $flags;

    public function __construct(int $flags)
    {
        $this->flags = $flags;
    }

    /**
     * Assert that this condition applies to the variable $var (i.e. $var < $expr)
     *
     * @param Node $var
     * @param Node|int|string|float $expr
     * @return Context
     * @override
     */
    public function analyzeVar(ConditionVisitorInterface $visitor, Node $var, $expr) : Context
    {
        return $visitor->updateVariableToBeCompared($var, $expr, $this->flags);
    }

    /**
     * Assert that this condition applies to the variable $object (i.e. get_class($object) === $expr)
     *
     * @param Node|int|string|float $object
     * @param Node|int|string|float $expr
     * @return Context
     * @suppress PhanUnusedPublicMethodParameter
     */
    public function analyzeClassCheck(ConditionVisitorInterface $visitor, $object, $expr) : Context
    {
        return $visitor->getContext();
    }
}
