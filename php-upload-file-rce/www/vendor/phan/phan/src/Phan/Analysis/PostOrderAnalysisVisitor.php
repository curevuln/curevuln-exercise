<?php declare(strict_types=1);

namespace Phan\Analysis;

use AssertionError;
use ast;
use ast\flags;
use ast\Node;
use Exception;
use Phan\AST\AnalysisVisitor;
use Phan\AST\ContextNode;
use Phan\AST\PhanAnnotationAdder;
use Phan\AST\UnionTypeVisitor;
use Phan\CodeBase;
use Phan\Config;
use Phan\Exception\CodeBaseException;
use Phan\Exception\EmptyFQSENException;
use Phan\Exception\FQSENException;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Issue;
use Phan\IssueFixSuggester;
use Phan\Language\Context;
use Phan\Language\Element\Clazz;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\Method;
use Phan\Language\Element\Parameter;
use Phan\Language\Element\PassByReferenceVariable;
use Phan\Language\Element\Property;
use Phan\Language\Element\Variable;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\Type;
use Phan\Language\Type\ArrayType;
use Phan\Language\Type\FalseType;
use Phan\Language\Type\GenericArrayType;
use Phan\Language\Type\MixedType;
use Phan\Language\Type\NullType;
use Phan\Language\Type\StringType;
use Phan\Language\Type\VoidType;
use Phan\Language\UnionType;

/**
 * PostOrderAnalysisVisitor is where we do the post-order part of the analysis
 * during Phan's analysis phase.
 *
 * This is called in post-order by BlockAnalysisVisitor
 * (i.e. this is called after visiting all children of the current node)
 *
 * @phan-file-suppress PhanPartialTypeMismatchArgument
 */
class PostOrderAnalysisVisitor extends AnalysisVisitor
{
    /**
     * @var array<int,Node> a list of parent nodes of the currently analyzed node,
     * within the current global or function-like scope
     */
    private $parent_node_list;

    /**
     * @param CodeBase $code_base
     * A code base needs to be passed in because we require
     * it to be initialized before any classes or files are
     * loaded.
     *
     * @param Context $context
     * The context of the parser at the node for which we'd
     * like to determine a type
     *
     * @param array<int,Node> $parent_node_list
     * The parent node list of the node being analyzed
     */
    public function __construct(
        CodeBase $code_base,
        Context $context,
        array $parent_node_list
    ) {
        parent::__construct($code_base, $context);
        $this->parent_node_list = $parent_node_list;
    }

    /**
     * Default visitor for node kinds that do not have
     * an overriding method
     *
     * @param Node $node (@phan-unused-param)
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visit(Node $node) : Context
    {
        // Many nodes don't change the context and we
        // don't need to read them.
        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitAssign(Node $node) : Context
    {
        // Get the type of the right side of the
        // assignment
        $right_type = UnionTypeVisitor::unionTypeFromNode(
            $this->code_base,
            $this->context,
            $node->children['expr'],
            true
        );

        $var_node = $node->children['var'];
        if (!($var_node instanceof Node)) {
            // Give up, this should be impossible except with the fallback
            $this->emitIssue(
                Issue::InvalidNode,
                $node->lineno ?? 0,
                "Expected left side of assignment to be a variable"
            );
            return $this->context;
        }

        if ($right_type->isType(VoidType::instance(false))) {
            $this->emitIssue(
                Issue::TypeVoidAssignment,
                $node->lineno ?? 0
            );
        }

        // Handle the assignment based on the type of the
        // right side of the equation and the kind of item
        // on the left
        $context = (new AssignmentVisitor(
            $this->code_base,
            $this->context,
            $node,
            $right_type
        ))->__invoke($var_node);

        $expr_node = $node->children['expr'];
        if ($expr_node instanceof Node
            && $expr_node->kind == ast\AST_CLOSURE
        ) {
            $method = (new ContextNode(
                $this->code_base,
                $this->context->withLineNumberStart(
                    $expr_node->lineno ?? 0
                ),
                $expr_node
            ))->getClosure();

            $method->addReference($this->context);
        }

        return $context;
    }

    /**
     * @param Node $node (@phan-unused-param)
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitAssignRef(Node $node) : Context
    {
        return $this->visitAssign($node);
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     * @override
     */
    public function visitAssignOp(Node $node) : Context
    {
        return (new AssignOperatorAnalysisVisitor($this->code_base, $this->context))->__invoke($node);
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitUnset(Node $node) : Context
    {
        $context = $this->context;
        // Get the type of the thing being unset
        $var_node = $node->children['var'];
        if (!($var_node instanceof Node)) {
            return $context;
        }

        $kind = $var_node->kind;
        if ($kind === ast\AST_VAR) {
            $var_name = $var_node->children['name'];
            if (\is_string($var_name)) {
                // TODO: Make this work in branches
                $context->unsetScopeVariable($var_name);
            }
            // I think DollarDollarPlugin already warns, so don't warn here.
        } elseif ($kind === ast\AST_DIM) {
            $this->analyzeUnsetDim($var_node);
        } elseif ($kind === ast\AST_PROP) {
            $this->analyzeUnsetProp($var_node);
        }
        return $context;
    }

    /**
     * @param Node $node a node of type AST_DIM in unset()
     * @return void
     * @see UnionTypeVisitor::resolveArrayShapeElementTypes()
     * @see UnionTypeVisitor::visitDim()
     */
    private function analyzeUnsetDim(Node $node)
    {
        $expr_node = $node->children['expr'];
        if (!($expr_node instanceof Node)) {
            // php -l would warn
            return;
        }

        // For now, just handle a single level of dimensions for unset($x['field']);
        if ($expr_node->kind === ast\AST_VAR) {
            $var_name = $expr_node->children['name'];
            if (!\is_string($var_name)) {
                return;
            }

            $context = $this->context;
            $scope = $context->getScope();
            if (!$scope->hasVariableWithName($var_name)) {
                // TODO: Warn about potentially pointless unset in function scopes?
                return;
            }
            // TODO: Could warn about invalid offsets for isset
            $variable = $scope->getVariableByName($var_name);
            $union_type = $variable->getUnionType();
            if ($union_type->isEmpty()) {
                return;
            }
            if (!$union_type->asExpandedTypes($this->code_base)->hasArrayLike() && !$union_type->hasMixedType()) {
                $this->emitIssue(
                    Issue::TypeArrayUnsetSuspicious,
                    $node->lineno ?? 0,
                    (string)$union_type
                );
            }
            if (!$union_type->hasTopLevelArrayShapeTypeInstances()) {
                return;
            }
            $dim_node = $node->children['dim'];
            $dim_value = $dim_node instanceof Node ? (new ContextNode($this->code_base, $this->context, $dim_node))->getEquivalentPHPScalarValue() : $dim_node;
            // TODO: detect and warn about null
            if (!\is_scalar($dim_value)) {
                return;
            }
            $variable->setUnionType($variable->getUnionType()->withoutArrayShapeField($dim_value));
        }
    }

    /**
     * @param Node $node a node of type AST_PROP in unset()
     * @return void
     * @see UnionTypeVisitor::resolveArrayShapeElementTypes()
     * @see UnionTypeVisitor::visitDim()
     */
    private function analyzeUnsetProp(Node $node)
    {
        $expr_node = $node->children['expr'];
        if (!($expr_node instanceof Node)) {
            // php -l would warn
            return;
        }
        $prop_name = $node->children['prop'];
        if (!\is_string($prop_name)) {
            $prop_name = (new ContextNode($this->code_base, $this->context, $prop_name))->getEquivalentPHPScalarValue();
            if (!\is_string($prop_name)) {
                return;
            }
        }

        $union_type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $this->context, $expr_node);
        $type_fqsens = $union_type->objectTypesWithKnownFQSENs();
        foreach ($type_fqsens->getTypeSet() as $type) {
            $fqsen = FullyQualifiedClassName::fromType($type);
            if (!$this->code_base->hasClassWithFQSEN($fqsen)) {
                continue;
            }
            $class = $this->code_base->getClassByFQSEN($fqsen);
            if ($class->hasPropertyWithName($this->code_base, $prop_name)) {
                // NOTE: We deliberately emit this issue whether or not the access is to a public or private variable,
                // because unsetting a private variable at runtime is also a (failed) attempt to unset a declared property.
                $prop = $class->getPropertyByName($this->code_base, $prop_name);
                if ($prop->isFromPHPDoc() || $prop->isDynamicProperty()) {
                    continue;
                }
                $this->emitIssue(
                    Issue::TypeObjectUnsetDeclaredProperty,
                    $node->lineno ?? 0,
                    (string)$type,
                    $prop_name,
                    $prop->getFileRef()->getFile(),
                    $prop->getFileRef()->getLineNumberStart()
                );
            }
        }
    }

    /**
     * @param Node $node (@phan-unused-param)
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitIfElem(Node $node) : Context
    {
        return $this->context;
    }

    /**
     * @param Node $node @phan-unused-param
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitWhile(Node $node) : Context
    {
        return $this->context;
    }

    /**
     * @param Node $node @phan-unused-param
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitSwitch(Node $node) : Context
    {
        return $this->context;
    }

    /**
     * @param Node $node @phan-unused-param
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitSwitchCase(Node $node) : Context
    {
        return $this->context;
    }

    /**
     * @param Node $node @phan-unused-param
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitExprList(Node $node) : Context
    {
        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitEncapsList(Node $node) : Context
    {
        $this->analyzeNoOp($node, Issue::NoopEncapsulatedStringLiteral);

        foreach ($node->children as $child_node) {
            // Confirm that variables exists
            if (!($child_node instanceof Node)) {
                continue;
            }
            $this->checkExpressionInDynamicString($child_node);
        }

        return $this->context;
    }

    /**
     * @return void
     */
    private function checkExpressionInDynamicString(Node $expr_node)
    {
        $code_base = $this->code_base;
        $context = $this->context;
        $type = UnionTypeVisitor::unionTypeFromNode(
            $code_base,
            $context,
            $expr_node,
            true
        );

        if (!$type->hasPrintableScalar()) {
            if ($type->isType(ArrayType::instance(false))
                || $type->isType(ArrayType::instance(true))
                || $type->isGenericArray()
            ) {
                $this->emitIssue(
                    Issue::TypeConversionFromArray,
                    $expr_node->lineno,
                    'string'
                );
                return;
            }
            // Check for __toString(), stringable variables/expressions in encapsulated strings work whether or not strict_types is set
            try {
                foreach ($type->asExpandedTypes($code_base)->asClassList($code_base, $context) as $clazz) {
                    if ($clazz->hasMethodWithName($code_base, "__toString")) {
                        return;
                    }
                }
            } catch (CodeBaseException $_) {
                // Swallow "Cannot find class", go on to emit issue
            }
            $this->emitIssue(
                Issue::TypeSuspiciousStringExpression,
                $expr_node->lineno,
                (string)$type
            );
        }
    }

    /**
     * Check if a given variable is undeclared.
     * @param Node $node Node with kind AST_VAR
     * @return void
     */
    private function checkForUndeclaredVariable(Node $node)
    {
        $variable_name = $node->children['name'];

        // Ignore $$var type things
        if (!\is_string($variable_name)) {
            return;
        }

        // Don't worry about non-existent undeclared variables
        // in the global scope if configured to do so
        if (Config::getValue('ignore_undeclared_variables_in_global_scope')
            && $this->context->isInGlobalScope()
        ) {
            return;
        }

        if (!$this->context->getScope()->hasVariableWithName($variable_name)
            && !Variable::isHardcodedVariableInScopeWithName($variable_name, $this->context->isInGlobalScope())
        ) {
            $this->emitIssueWithSuggestion(
                Issue::UndeclaredVariable,
                $node->lineno ?? 0,
                [$variable_name],
                IssueFixSuggester::suggestVariableTypoFix($this->code_base, $this->context, $variable_name)
            );
        }
    }

    /**
     * @param Node $node (@phan-unused-param)
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitDoWhile(Node $node) : Context
    {
        return $this->context;
    }

    /**
     * Visit a node with kind `ast\AST_GLOBAL`
     *
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitGlobal(Node $node) : Context
    {
        $variable = Variable::fromNodeInContext(
            $node->children['var'],
            $this->context,
            $this->code_base,
            false
        );
        $variable_name = $variable->getName();
        $optional_global_variable_type = Variable::getUnionTypeOfHardcodedGlobalVariableWithName($variable_name);
        if ($optional_global_variable_type) {
            $variable->setUnionType($optional_global_variable_type);
        } else {
            $scope = $this->context->getScope();
            if ($scope->hasGlobalVariableWithName($variable_name)) {
                // TODO: Support @global, add a clone to the method context?
                $actual_global_variable = $scope->getGlobalVariableByName($variable_name);
                $this->context->addScopeVariable($actual_global_variable);
                return $this->context;
            }
        }

        // Note that we're not creating a new scope, just
        // adding variables to the existing scope
        $this->context->addScopeVariable($variable);

        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitStatic(Node $node) : Context
    {
        $variable = Variable::fromNodeInContext(
            $node->children['var'],
            $this->context,
            $this->code_base,
            false
        );

        // If the element has a default, set its type
        // on the variable
        if (isset($node->children['default'])) {
            $default_type = UnionTypeVisitor::unionTypeFromNode(
                $this->code_base,
                $this->context,
                $node->children['default']
            );

            $variable->setUnionType($default_type);
        }

        // Note that we're not creating a new scope, just
        // adding variables to the existing scope
        $this->context->addScopeVariable($variable);

        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitEcho(Node $node) : Context
    {
        return $this->visitPrint($node);
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitPrint(Node $node) : Context
    {
        $code_base = $this->code_base;
        $context = $this->context;
        $expr_node = $node->children['expr'];
        $type = UnionTypeVisitor::unionTypeFromNode(
            $code_base,
            $context,
            $expr_node,
            true
        );

        if (!$type->hasPrintableScalar()) {
            if ($type->isType(ArrayType::instance(false))
                || $type->isType(ArrayType::instance(true))
                || $type->isGenericArray()
            ) {
                $this->emitIssue(
                    Issue::TypeConversionFromArray,
                    $expr_node->lineno ?? $node->lineno,
                    'string'
                );
                return $context;
            }
            if (!$context->getIsStrictTypes()) {
                try {
                    foreach ($type->asExpandedTypes($code_base)->asClassList($code_base, $context) as $clazz) {
                        if ($clazz->hasMethodWithName($code_base, "__toString")) {
                            return $context;
                        }
                    }
                } catch (CodeBaseException $_) {
                    // Swallow "Cannot find class", go on to emit issue
                }
            }
            $this->emitIssue(
                Issue::TypeSuspiciousEcho,
                $expr_node->lineno ?? $node->lineno,
                (string)$type
            );
        }

        return $context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitVar(Node $node) : Context
    {
        $this->analyzeNoOp($node, Issue::NoopVariable);
        $parent_node = \end($this->parent_node_list);
        if ($parent_node instanceof Node) {
            $parent_kind = $parent_node->kind;
            /**
             * These types are either types which create variables,
             * or types which will be checked in other parts of Phan
             */
            static $skip_var_check_types = [
                ast\AST_ARG_LIST       => true,  // may be a reference
                ast\AST_ARRAY_ELEM     => true,  // [$X, $y] = expr() is an AST_ARRAY_ELEM. visitArray() checks the right-hand side.
                ast\AST_ASSIGN_OP      => true,  // checked in visitAssignOp
                ast\AST_ASSIGN_REF     => true,  // Creates by reference?
                ast\AST_ASSIGN         => true,  // checked in visitAssign
                ast\AST_DIM            => true,  // should be checked elsewhere, as part of check for array access to non-array/string
                ast\AST_EMPTY          => true,  // TODO: Enable this in the future?
                ast\AST_GLOBAL         => true,  // global $var;
                ast\AST_ISSET          => true,  // TODO: Enable this in the future?
                ast\AST_PARAM_LIST     => true,  // this creates the variable
                ast\AST_STATIC         => true,  // static $var;
                ast\AST_STMT_LIST      => true,  // ;$var; (Implicitly creates the variable. Already checked to emit PhanNoopVariable)
                ast\AST_USE_ELEM       => true,  // may be a reference, checked elsewhere
            ];

            if (!\array_key_exists($parent_kind, $skip_var_check_types)) {
                $this->checkForUndeclaredVariable($node);
            }
        }
        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitArray(Node $node) : Context
    {
        $this->analyzeNoOp($node, Issue::NoopArray);
        return $this->context;
    }

    /** @internal */
    const NAME_FOR_BINARY_OP = [
        flags\BINARY_BOOL_AND            => '&&',
        flags\BINARY_BOOL_OR             => '||',
        flags\BINARY_BOOL_XOR            => 'xor',
        flags\BINARY_BITWISE_OR          => '|',
        flags\BINARY_BITWISE_AND         => '&',
        flags\BINARY_BITWISE_XOR         => '^',
        flags\BINARY_CONCAT              => '.',
        flags\BINARY_ADD                 => '+',
        flags\BINARY_SUB                 => '-',
        flags\BINARY_MUL                 => '*',
        flags\BINARY_DIV                 => '/',
        flags\BINARY_MOD                 => '%',
        flags\BINARY_POW                 => '**',
        flags\BINARY_SHIFT_LEFT          => '<<',
        flags\BINARY_SHIFT_RIGHT         => '>>',
        flags\BINARY_IS_IDENTICAL        => '===',
        flags\BINARY_IS_NOT_IDENTICAL    => '!==',
        flags\BINARY_IS_EQUAL            => '==',
        flags\BINARY_IS_NOT_EQUAL        => '!=',
        flags\BINARY_IS_SMALLER          => '<',
        flags\BINARY_IS_SMALLER_OR_EQUAL => '<=',
        flags\BINARY_IS_GREATER          => '>',
        flags\BINARY_IS_GREATER_OR_EQUAL => '>=',
        flags\BINARY_SPACESHIP           => '<=>',
        flags\BINARY_COALESCE            => '??',
    ];

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitBinaryOp(Node $node) : Context
    {
        if ((\end($this->parent_node_list)->kind ?? null) === ast\AST_STMT_LIST) {
            if (!\in_array($node->flags, [flags\BINARY_BOOL_AND, flags\BINARY_BOOL_OR, flags\BINARY_COALESCE])) {
                $this->emitIssue(
                    Issue::NoopBinaryOperator,
                    $node->lineno,
                    self::NAME_FOR_BINARY_OP[$node->flags] ?? ''
                );
            }
        }
        if ($node->flags === flags\BINARY_CONCAT) {
            $this->analyzeBinaryConcat($node);
        }
        return $this->context;
    }

    private function analyzeBinaryConcat(Node $node)
    {
        $left = $node->children['left'];
        if ($left instanceof Node) {
            $this->checkExpressionInDynamicString($left);
        }
        $right = $node->children['right'];
        if ($right instanceof Node) {
            $this->checkExpressionInDynamicString($right);
        }
    }

    const NAME_FOR_UNARY_OP = [
        flags\UNARY_BOOL_NOT => '!',
        flags\UNARY_BITWISE_NOT => '~',
        flags\UNARY_SILENCE => '@',
        flags\UNARY_PLUS => '+',
        flags\UNARY_MINUS => '-',
    ];

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitUnaryOp(Node $node) : Context
    {
        if ($node->flags !== flags\UNARY_SILENCE) {
            if ((\end($this->parent_node_list)->kind ?? null) === ast\AST_STMT_LIST) {
                $this->emitIssue(
                    Issue::NoopUnaryOperator,
                    $node->lineno,
                    self::NAME_FOR_UNARY_OP[$node->flags] ?? ''
                );
            }
        }
        return $this->context;
    }

    /**
     * @override
     */
    public function visitPreInc(Node $node) : Context
    {
        return $this->analyzeIncOrDec($node);
    }

    /**
     * @override
     */
    public function visitPostInc(Node $node) : Context
    {
        return $this->analyzeIncOrDec($node);
    }

    /**
     * @override
     */
    public function visitPreDec(Node $node) : Context
    {
        return $this->analyzeIncOrDec($node);
    }

    /**
     * @override
     */
    public function visitPostDec(Node $node) : Context
    {
        return $this->analyzeIncOrDec($node);
    }

    const NAME_FOR_INC_OR_DEC_KIND = [
        ast\AST_PRE_INC => '++(expr)',
        ast\AST_PRE_DEC => '--(expr)',
        ast\AST_POST_INC => '(expr)++',
        ast\AST_POST_DEC => '(expr)--',
    ];

    private function analyzeIncOrDec(Node $node) : Context
    {
        $var = $node->children['var'];
        $old_type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $this->context, $var)->withFlattenedArrayShapeOrLiteralTypeInstances();
        if (!$old_type->canCastToUnionType(UnionType::fromFullyQualifiedString('int|string|float'))) {
            $this->emitIssue(
                Issue::TypeInvalidUnaryOperandIncOrDec,
                $node->lineno,
                self::NAME_FOR_INC_OR_DEC_KIND[$node->kind],
                $old_type
            );
        }
        // The left can be a non-Node for an invalid AST
        $kind = $var->kind ?? null;
        if ($kind === \ast\AST_VAR) {
            $new_type = $old_type->getTypeAfterIncOrDec();
            if ($old_type === $new_type) {
                return $this->context;
            }
            try {
                $variable = (new ContextNode($this->code_base, $this->context, $node->children['var']))->getVariableStrict();
            } catch (IssueException $_) {
                return $this->context;
            } catch (NodeException $_) {
                return $this->context;
            }
            $variable->setUnionType($new_type);
        }
        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitConst(Node $node) : Context
    {
        $context = $this->context;
        try {
            $name_node = $node->children['name'];
            // Based on UnionTypeVisitor::visitConst
            if ($name_node->kind == ast\AST_NAME) {
                $constant = (new ContextNode(
                    $this->code_base,
                    $context,
                    $node
                ))->getConst();

                // Mark that this constant has been referenced from
                // this context
                $constant->addReference($context);
            }
        } catch (IssueException $exception) {
            // We need to do this in order to check keys and (after the first 5) values in AST arrays.
            // Other parts of the AST may also not be covered.
            // (This issue may be a duplicate)
            Issue::maybeEmitInstance(
                $this->code_base,
                $context,
                $exception->getIssueInstance()
            );
        } catch (Exception $_) {
            // Swallow any other types of exceptions. We'll log the errors
            // elsewhere.
        }

        // Check to make sure we're doing something with the
        // constant
        $this->analyzeNoOp($node, Issue::NoopConstant);

        return $context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitClassConst(Node $node) : Context
    {
        try {
            $constant = (new ContextNode(
                $this->code_base,
                $this->context,
                $node
            ))->getClassConst();

            // Mark that this class constant has been referenced
            // from this context
            $constant->addReference($this->context);
        } catch (IssueException $exception) {
            // We need to do this in order to check keys and (after the first 5) values in AST arrays, possibly other types.
            Issue::maybeEmitInstance(
                $this->code_base,
                $this->context,
                $exception->getIssueInstance()
            );
        } catch (Exception $_) {
            // Swallow any other types of exceptions. We'll log the errors
            // elsewhere.
        }

        // Check to make sure we're doing something with the
        // class constant
        $this->analyzeNoOp($node, Issue::NoopConstant);

        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitClosure(Node $node) : Context
    {
        $func = $this->context->getFunctionLikeInScope($this->code_base);

        $return_type = $func->getUnionType();

        if (!$return_type->isEmpty()
            && !$func->getHasReturn()
            && !$this->declOnlyThrows($node)
            && !$return_type->hasType(VoidType::instance(false))
            && !$return_type->hasType(NullType::instance(false))
        ) {
            $this->emitIssue(
                Issue::TypeMissingReturn,
                $node->lineno ?? 0,
                (string)$func->getFQSEN(),
                (string)$return_type
            );
        }
        $this->analyzeNoOp($node, Issue::NoopClosure);
        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitReturn(Node $node) : Context
    {
        $context = $this->context;
        // Make sure we're actually returning from a method.
        if (!$context->isInFunctionLikeScope()) {
            return $context;
        }
        $code_base = $this->code_base;

        // Check real return types instead of phpdoc return types in traits for #800
        // TODO: Why did Phan originally not analyze return types of traits at all in 4c6956c05222e093b29393ceaa389ffb91041bdc
        $is_trait = false;
        if ($context->isInClassScope()) {
            $clazz = $context->getClassInScope($code_base);
            $is_trait = $clazz->isTrait();
        }

        // Get the method/function/closure we're in
        $method = $context->getFunctionLikeInScope($code_base);

        if ($method->getHasYield()) {  // Function that is syntactically a Generator.
            $this->analyzeReturnInGenerator($method, $node);
            // TODO: Compare against TReturn of Generator<TKey,TValue,TSend,TReturn>
            return $context;  // Analysis was completed in PreOrderAnalysisVisitor
        }

        // Figure out what we intend to return
        // (For traits, lower the false positive rate by comparing against the real return type instead of the phpdoc type (#800))
        $method_return_type = $is_trait ? $method->getRealReturnType()->withAddedClassForResolvedSelf($method->getContext()) : $method->getUnionType();

        // This leaves functions which aren't syntactically generators.

        // Figure out what is actually being returned
        // TODO: Properly check return values of array shapes
        foreach ($this->getReturnTypes($context, $node->children['expr'], $node->lineno) as $lineno => $expression_type) {
            // If there is no declared type, see if we can deduce
            // what it should be based on the return type
            if ($method_return_type->isEmpty()
                || $method->isReturnTypeUndefined()
            ) {
                if (!$is_trait) {
                    $method->setIsReturnTypeUndefined(true);

                    // Set the inferred type of the method based
                    // on what we're returning
                    $method->setUnionType($method->getUnionType()->withUnionType($expression_type));
                }

                // No point in comparing this type to the
                // type we just set
                continue;
            }

            // Check if the return type is compatible with the declared return type.
            if (!$method->isReturnTypeUndefined()) {
                // We allow base classes to cast to subclasses, and subclasses to cast to base classes,
                // but don't allow subclasses to cast to subclasses on a separate branch of the inheritance tree
                if (!$this->checkCanCastToReturnType($code_base, $expression_type, $method_return_type)) {
                    $this->emitIssue(
                        Issue::TypeMismatchReturn,
                        $lineno,
                        (string)$expression_type,
                        $method->getNameForIssue(),
                        (string)$method_return_type
                    );
                } elseif (Config::get_strict_return_checking() && $expression_type->typeCount() > 1) {
                    self::analyzeReturnStrict($code_base, $method, $expression_type, $method_return_type, $lineno);
                }
            }
            // For functions that aren't syntactically Generators,
            // update the set/existence of return values.

            if ($method->isReturnTypeUndefined()) {
                // Add the new type to the set of values returned by the
                // method
                $method->setUnionType($method->getUnionType()->withUnionType($expression_type));
            }

            // Mark the method as returning something (even if void)
            if (null !== $node->children['expr']) {
                $method->setHasReturn(true);
            }
        }

        return $context;
    }

    /**
     * @return void
     */
    private function analyzeReturnInGenerator(
        FunctionInterface $method,
        Node $node
    ) {
        $method_generator_type = $method->getReturnTypeAsGeneratorTemplateType();
        $type_list = $method_generator_type->getTemplateParameterTypeList();
        // Generator<TKey,TValue,TSend,TReturn>
        if (\count($type_list) !== 4) {
            return;
        }
        $expected_return_type = $type_list[3];
        if ($expected_return_type->isEmpty()) {
            return;
        }

        $context = $this->context;
        $code_base = $this->code_base;

        foreach ($this->getReturnTypes($context, $node->children['expr'], $node->lineno) as $lineno => $expression_type) {
            // We allow base classes to cast to subclasses, and subclasses to cast to base classes,
            // but don't allow subclasses to cast to subclasses on a separate branch of the inheritance tree
            if (!$this->checkCanCastToReturnType($code_base, $expression_type, $expected_return_type)) {
                $this->emitIssue(
                    Issue::TypeMismatchReturn,
                    $lineno,
                    (string)$expression_type,
                    $method->getNameForIssue(),
                    (string)$expected_return_type
                );
            } elseif (Config::get_strict_return_checking() && $expression_type->typeCount() > 1) {
                self::analyzeReturnStrict($code_base, $method, $expression_type, $expected_return_type, $lineno);
            }
        }
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitYield(Node $node) : Context
    {
        $context = $this->context;
        // Make sure we're actually returning from a method.
        if (!$context->isInFunctionLikeScope()) {
            return $context;
        }

        // Get the method/function/closure we're in
        $method = $context->getFunctionLikeInScope($this->code_base);

        // Figure out what we intend to return
        $method_generator_type = $method->getReturnTypeAsGeneratorTemplateType();
        $type_list = $method_generator_type->getTemplateParameterTypeList();
        if (\count($type_list) === 0) {
            return $context;
        }
        return $this->compareYieldAgainstDeclaredType($node, $method, $context, $type_list);
    }

    /**
     * @param array<int,UnionType> $template_type_list
     */
    private function compareYieldAgainstDeclaredType(Node $node, FunctionInterface $method, Context $context, array $template_type_list) : Context
    {
        $code_base = $this->code_base;

        $type_list_count = \count($template_type_list);

        $yield_value_node = $node->children['value'];
        if ($yield_value_node === null) {
            $yield_value_type = VoidType::instance(false)->asUnionType();
        } else {
            $yield_value_type = UnionTypeVisitor::unionTypeFromNode($code_base, $context, $yield_value_node);
        }
        $expected_value_type = $template_type_list[\min(1, $type_list_count - 1)];
        if (!$yield_value_type->asExpandedTypes($code_base)->canCastToUnionType($expected_value_type)) {
            $this->emitIssue(
                Issue::TypeMismatchGeneratorYieldValue,
                $node->lineno,
                (string)$yield_value_type,
                $method->getNameForIssue(),
                (string)$expected_value_type,
                '\Generator<' . implode(',', $template_type_list) . '>'
            );
        }

        if ($type_list_count > 1) {
            $yield_key_node = $node->children['key'];
            if ($yield_key_node === null) {
                $yield_key_type = VoidType::instance(false)->asUnionType();
            } else {
                $yield_key_type = UnionTypeVisitor::unionTypeFromNode($code_base, $context, $yield_key_node);
            }
            // TODO: finalize syntax to indicate the absence of a key or value (e.g. use void instead?)
            $expected_key_type = $template_type_list[0];
            if (!$yield_key_type->asExpandedTypes($code_base)->canCastToUnionType($expected_key_type)) {
                $this->emitIssue(
                    Issue::TypeMismatchGeneratorYieldKey,
                    $node->lineno,
                    (string)$yield_key_type,
                    $method->getNameForIssue(),
                    (string)$expected_key_type,
                    '\Generator<' . implode(',', $template_type_list) . '>'
                );
            }
        }
        return $context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitYieldFrom(Node $node) : Context
    {
        $context = $this->context;
        // Make sure we're actually returning from a method.
        if (!$context->isInFunctionLikeScope()) {
            return $context;
        }

        // Get the method/function/closure we're in
        $method = $context->getFunctionLikeInScope($this->code_base);
        $code_base = $this->code_base;

        $yield_from_type = UnionTypeVisitor::unionTypeFromNode($code_base, $context, $node->children['expr']);
        if ($yield_from_type->isEmpty()) {
            return $context;
        }
        $yield_from_expanded_type = $yield_from_type->asExpandedTypes($code_base);
        if (!$yield_from_expanded_type->hasIterable() && !$yield_from_expanded_type->hasTraversable()) {
            $this->emitIssue(
                Issue::TypeInvalidYieldFrom,
                $node->lineno,
                (string)$yield_from_type
            );
            return $context;
        }


        // Figure out what we intend to return
        $method_generator_type = $method->getReturnTypeAsGeneratorTemplateType();
        $type_list = $method_generator_type->getTemplateParameterTypeList();
        if (\count($type_list) === 0) {
            return $context;
        }
        return $this->compareYieldFromAgainstDeclaredType($node, $method, $context, $type_list, $yield_from_type);
    }

    /**
     * @param array<int,UnionType> $template_type_list
     */
    private function compareYieldFromAgainstDeclaredType(Node $node, FunctionInterface $method, Context $context, array $template_type_list, UnionType $yield_from_type) : Context
    {
        $code_base = $this->code_base;

        $type_list_count = \count($template_type_list);

        // TODO: Can do a better job of analyzing expressions that are just arrays or subclasses of Traversable.
        //
        // A solution would need to check for (at)return Generator|T[]
        $yield_from_generator_type = $yield_from_type->asGeneratorTemplateType();

        $actual_template_type_list = $yield_from_generator_type->getTemplateParameterTypeList();
        $actual_type_list_count = \count($actual_template_type_list);
        if ($actual_type_list_count === 0) {
            return $context;
        }

        $yield_value_type = $actual_template_type_list[\min(1, $actual_type_list_count - 1)];
        $expected_value_type = $template_type_list[\min(1, $type_list_count - 1)];
        if (!$yield_value_type->asExpandedTypes($code_base)->canCastToUnionType($expected_value_type)) {
            $this->emitIssue(
                Issue::TypeMismatchGeneratorYieldValue,
                $node->lineno,
                (string)$yield_value_type,
                $method->getNameForIssue(),
                (string)$expected_value_type,
                '\Generator<' . implode(',', $template_type_list) . '>'
            );
        }

        if ($type_list_count > 1 && $actual_type_list_count > 1) {
            // TODO: finalize syntax to indicate the absence of a key or value (e.g. use void instead?)
            $yield_key_type = $actual_template_type_list[0];
            $expected_key_type = $template_type_list[0];
            if (!$yield_key_type->asExpandedTypes($code_base)->canCastToUnionType($expected_key_type)) {
                $this->emitIssue(
                    Issue::TypeMismatchGeneratorYieldKey,
                    $node->lineno,
                    (string)$yield_key_type,
                    $method->getNameForIssue(),
                    (string)$expected_key_type,
                    '\Generator<' . implode(',', $template_type_list) . '>'
                );
            }
        }
        return $context;
    }

    private function checkCanCastToReturnType(CodeBase $code_base, UnionType $expression_type, UnionType $method_return_type) : bool
    {
        if ($method_return_type->hasTemplateParameterTypes()) {
            // Perform a check that does a better job understanding rules of templates.
            // (E.g. should be able to cast None to Option<MyClass>, but not Some<int> to Option<MyClass>
            return $expression_type->asExpandedTypesPreservingTemplate($code_base)->canCastToUnionTypeHandlingTemplates($method_return_type, $code_base) ||
                $expression_type->canCastToUnionTypeHandlingTemplates($method_return_type->asExpandedTypesPreservingTemplate($code_base), $code_base);
        }
        // We allow base classes to cast to subclasses, and subclasses to cast to base classes,
        // but don't allow subclasses to cast to subclasses on a separate branch of the inheritance tree
        return $expression_type->asExpandedTypes($code_base)->canCastToUnionType($method_return_type) ||
            $expression_type->canCastToUnionType($method_return_type->asExpandedTypes($code_base));
    }

    /**
     * @return void
     */
    private function analyzeReturnStrict(
        CodeBase $code_base,
        FunctionInterface $method,
        UnionType $expression_type,
        UnionType $method_return_type,
        int $lineno
    ) {
        $type_set = $expression_type->getTypeSet();
        $context = $this->context;
        if (\count($type_set) < 2) {
            throw new AssertionError("Expected at least two types for strict return type checks");
        }

        $mismatch_type_set = UnionType::empty();
        $mismatch_expanded_types = null;

        // For the strict
        foreach ($type_set as $type) {
            // Expand it to include all parent types up the chain
            $individual_type_expanded = $type->asExpandedTypes($code_base);

            // See if the argument can be cast to the
            // parameter
            if (!$individual_type_expanded->canCastToUnionType(
                $method_return_type
            )) {
                if ($method->isPHPInternal()) {
                    // If we are not in strict mode and we accept a string parameter
                    // and the argument we are passing has a __toString method then it is ok
                    if (!$context->getIsStrictTypes() && $method_return_type->hasNonNullStringType()) {
                        if ($individual_type_expanded->hasClassWithToStringMethod($code_base, $context)) {
                            continue;
                        }
                    }
                }
                $mismatch_type_set = $mismatch_type_set->withType($type);
                if ($mismatch_expanded_types === null) {
                    // Warn about the first type
                    $mismatch_expanded_types = $individual_type_expanded;
                }
            }
        }


        if ($mismatch_expanded_types === null) {
            // No mismatches
            return;
        }

        // If we have TypeMismatchReturn already, then also suppress the partial mismatch warnings (e.g. PartialTypeMismatchReturn) as well.
        if ($this->context->hasSuppressIssue($code_base, Issue::TypeMismatchReturn)) {
            return;
        }
        $this->emitIssue(
            self::getStrictIssueType($mismatch_type_set),
            $lineno,
            (string)$expression_type,
            $method->getNameForIssue(),
            (string)$method_return_type,
            $mismatch_expanded_types
        );
    }

    private static function getStrictIssueType(UnionType $union_type) : string
    {
        if ($union_type->typeCount() === 1) {
            $type = $union_type->getTypeSet()[0];
            if ($type instanceof NullType) {
                return Issue::PossiblyNullTypeReturn;
            }
            if ($type instanceof FalseType) {
                return Issue::PossiblyFalseTypeReturn;
            }
        }
        return Issue::PartialTypeMismatchReturn;
    }

    /**
     * @param ?Node|?string|?int|?float $node
     * @return \Generator|array<int,UnionType>
     * @phan-return \Generator<int,UnionType>
     */
    private function getReturnTypes(Context $context, $node, int $return_lineno)
    {
        if (!($node instanceof Node)) {
            if (null === $node) {
                yield $return_lineno => VoidType::instance(false)->asUnionType();
                return;
            }
            yield $return_lineno => UnionTypeVisitor::unionTypeFromNode(
                $this->code_base,
                $context,
                $node,
                true
            );
            return;
        }
        $kind = $node->kind;
        if ($kind === ast\AST_CONDITIONAL) {
            yield from self::deduplicateUnionTypes($this->getReturnTypesOfConditional($context, $node));
            return;
        } elseif ($kind === ast\AST_ARRAY) {
            $expression_type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $context, $node, true);
            if ($expression_type->hasTopLevelArrayShapeTypeInstances()) {
                yield $return_lineno => $expression_type;
                return;
            }

            $key_type_enum = GenericArrayType::getKeyTypeOfArrayNode($this->code_base, $context, $node);
            foreach (self::deduplicateUnionTypes($this->getReturnTypesOfArray($context, $node)) as $lineno => $elem_type) {
                yield $lineno => $elem_type->asGenericArrayTypes($key_type_enum);  // TODO: Infer corresponding key types
            }
            return;
        }

        $expression_type = UnionTypeVisitor::unionTypeFromNode(
            $this->code_base,
            $context,
            $node,
            true
        );

        if ($expression_type->hasStaticType()) {
            $expression_type =
                $expression_type->withStaticResolvedInContext(
                    $context
                );
        }
        yield $return_lineno => $expression_type;
    }

    /**
     * @return \Generator|UnionType[]
     * @phan-return \Generator<int,UnionType>
     */
    private function getReturnTypesOfConditional(Context $context, Node $node)
    {
        $cond_node = $node->children['cond'];
        $cond_truthiness = UnionTypeVisitor::checkCondUnconditionalTruthiness($cond_node);
        // For the shorthand $a ?: $b, the cond node will be the truthy value.
        // Note: an ast node will never be null(can be unset), it will be a const AST node with the name null.
        $true_node = $node->children['true'] ?? $cond_node;

        // Rarely, a conditional will always be true or always be false.
        if ($cond_truthiness !== null) {
            // TODO: Add no-op checks in another PR, if they don't already exist for conditional.
            if ($cond_truthiness === true) {
                // The condition is unconditionally true
                yield from $this->getReturnTypes($context, $true_node, $node->lineno);
                return;
            } else {
                // The condition is unconditionally false

                // Add the type for the 'false' side
                yield from $this->getReturnTypes($context, $node->children['false'], $node->lineno);
                return;
            }
        }

        // TODO: false_context once there is a NegatedConditionVisitor
        // TODO: emit no-op if $cond_node is a literal, such as `if (2)`
        // - Also note that some things such as `true` and `false` are ast\AST_NAME nodes.

        if ($cond_node instanceof Node) {
            // TODO: Use different contexts and merge those, in case there were assignments or assignments by reference in both sides of the conditional?
            // Reuse the BranchScope (sort of unintuitive). The ConditionVisitor returns a clone and doesn't modify the original.
            $base_context = $this->context;
            // We don't bother analyzing visitReturn in PostOrderAnalysisVisitor, right now.
            // This may eventually change, just to ensure the expression is checked for issues
            $true_context = (new ConditionVisitor(
                $this->code_base,
                $base_context
            ))->__invoke($cond_node);
            $false_context = (new NegatedConditionVisitor(
                $this->code_base,
                $base_context
            ))->__invoke($cond_node);
        } else {
            $true_context = $context;
            $false_context = $this->context;
        }

        // Allow nested ternary operators, or arrays within ternary operators
        if (($node->children['true'] ?? null) !== null) {
            yield from $this->getReturnTypes($true_context, $true_node, $true_node->lineno ?? $node->lineno);
        } else {
            // E.g. From the left-hand side of yield (int|false) ?: default,
            // yielding false is impossible.
            foreach ($this->getReturnTypes($true_context, $true_node, $true_node->lineno ?? $node->lineno) as $lineno => $raw_union_type) {
                if ($raw_union_type->isEmpty() || !$raw_union_type->containsFalsey()) {
                    yield $lineno => $raw_union_type;
                } else {
                    $raw_union_type = $raw_union_type->nonFalseyClone();
                    if (!$raw_union_type->isEmpty()) {
                        yield $lineno => $raw_union_type;
                    }
                }
            }
        }

        $false_node = $node->children['false'];
        yield from $this->getReturnTypes($false_context, $false_node, $false_node->lineno ?? $node->lineno);
    }

    /**
     * @param \Generator|UnionType[] $types
     * @return \Generator|array<int,UnionType>
     * @phan-return \Generator<int,UnionType>
     */
    private static function deduplicateUnionTypes($types)
    {
        $unique_types = [];
        foreach ($types as $lineno => $type) {
            foreach ($unique_types as $old_type) {
                if ($type->isEqualTo($old_type)) {
                    break;
                }
            }
            yield $lineno => $type;
            $unique_types[] = $type;
        }
    }

    /**
     * @return \Generator|UnionType[]
     * @phan-return \Generator<int,UnionType>
     */
    private function getReturnTypesOfArray(Context $context, Node $node)
    {
        if (count($node->children) === 0) {
            // Possibly unreachable (array shape would be returned instead)
            yield $node->lineno => MixedType::instance(false)->asUnionType();
            return;
        }
        foreach ($node->children as $elem) {
            if (!($elem instanceof Node)) {
                // We already emit PhanSyntaxError
                continue;
            }
            // Don't bother recursing more than one level to iterate over possible types.
            $value_node = $elem->children['value'];
            if ($value_node instanceof Node) {
                yield $elem->lineno => UnionTypeVisitor::unionTypeFromNode(
                    $this->code_base,
                    $context,
                    $value_node,
                    true
                );
            } else {
                yield $elem->lineno => Type::fromObject(
                    $value_node
                )->asUnionType();
            }
        }
    }

    /**
     * @param Node $node (@phan-unused-param)
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitPropDecl(Node $node) : Context
    {
        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitCall(Node $node) : Context
    {
        $expression = $node->children['expr'];
        try {
            $function_list_generator = (new ContextNode(
                $this->code_base,
                $this->context,
                $expression
            ))->getFunctionFromNode();

            foreach ($function_list_generator as $function) {
                // Check the call for parameter and argument types
                $this->analyzeCallToFunctionLike(
                    $function,
                    $node
                );
            }
        } catch (CodeBaseException $_) {
            // ignore it.
        }

        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitNew(Node $node) : Context
    {
        try {
            $context_node = (new ContextNode(
                $this->code_base,
                $this->context,
                $node
            ));

            $method = $context_node->getMethod(
                '__construct',
                false,
                false,
                true
            );

            $class_list = $context_node->getClassList(false, ContextNode::CLASS_LIST_ACCEPT_OBJECT_OR_CLASS_NAME);
            // Add a reference to each class this method
            // could be called on
            foreach ($class_list as $class) {
                $class->addReference($this->context);
                if ($class->isDeprecated()) {
                    $this->emitIssue(
                        Issue::DeprecatedClass,
                        $node->lineno ?? 0,
                        (string)$class->getFQSEN(),
                        $class->getContext()->getFile(),
                        (string)$class->getContext()->getLineNumberStart()
                    );
                }
                foreach ($class->getInterfaceFQSENList() as $interface) {
                    $clazz = $this->code_base->getClassByFQSEN($interface);
                    if ($clazz->isDeprecated()) {
                        $this->emitIssue(
                            Issue::DeprecatedInterface,
                            $node->lineno ?? 0,
                            (string)$clazz->getFQSEN(),
                            $clazz->getContext()->getFile(),
                            (string)$clazz->getContext()->getLineNumberStart()
                        );
                    }
                }
                foreach ($class->getTraitFQSENList() as $trait) {
                    $clazz = $this->code_base->getClassByFQSEN($trait);
                    if ($clazz->isDeprecated()) {
                        $this->emitIssue(
                            Issue::DeprecatedTrait,
                            $node->lineno ?? 0,
                            (string)$clazz->getFQSEN(),
                            $clazz->getContext()->getFile(),
                            (string)$clazz->getContext()->getLineNumberStart()
                        );
                    }
                }
            }

            $this->analyzeMethodVisibility(
                $method,
                $node
            );

            $this->analyzeCallToFunctionLike(
                $method,
                $node
            );

            foreach ($class_list as $class) {
                // Make sure we're not instantiating an abstract
                // class
                if ($class->isAbstract()
                    && (!$this->context->isInClassScope()
                    || $class->getFQSEN() != $this->context->getClassFQSEN())
                ) {
                    $this->emitIssue(
                        Issue::TypeInstantiateAbstract,
                        $node->lineno ?? 0,
                        (string)$class->getFQSEN()
                    );
                }

                // Make sure we're not instantiating an interface
                if ($class->isInterface()) {
                    $this->emitIssue(
                        Issue::TypeInstantiateInterface,
                        $node->lineno ?? 0,
                        (string)$class->getFQSEN()
                    );
                }
            }
        } catch (IssueException $exception) {
            Issue::maybeEmitInstance(
                $this->code_base,
                $this->context,
                $exception->getIssueInstance()
            );
        } catch (Exception $_) {
            // If we can't figure out what kind of a call
            // this is, don't worry about it
            return $this->context;
        }

        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitInstanceof(Node $node) : Context
    {
        try {
            // Fetch the class list, and emit warnings as a side effect.
            // TODO: Unify UnionTypeVisitor, AssignmentVisitor, and PostOrderAnalysisVisitor
            (new ContextNode(
                $this->code_base,
                $this->context,
                $node->children['class']
            ))->getClassList(false, ContextNode::CLASS_LIST_ACCEPT_OBJECT_OR_CLASS_NAME, Issue::TypeInvalidInstanceof);
        } catch (IssueException $exception) {
            Issue::maybeEmitInstance(
                $this->code_base,
                $this->context,
                $exception->getIssueInstance()
            );
        } catch (CodeBaseException $exception) {
            $this->emitIssueWithSuggestion(
                Issue::UndeclaredClassInstanceof,
                $node->lineno ?? 0,
                [(string)$exception->getFQSEN()],
                IssueFixSuggester::suggestSimilarClassForGenericFQSEN(
                    $this->code_base,
                    $this->context,
                    $exception->getFQSEN(),
                    // Only suggest classes/interfaces for alternatives to instanceof checks. Don't suggest traits.
                    IssueFixSuggester::createFQSENFilterForClasslikeCategories($this->code_base, true, false, true)
                )
            );
        }

        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitStaticCall(Node $node) : Context
    {
        // Get the name of the method being called
        $method_name = $node->children['method'];

        // Give up on things like Class::$var
        if (!\is_string($method_name)) {
            if ($method_name instanceof Node) {
                $method_name = UnionTypeVisitor::anyStringLiteralForNode($this->code_base, $this->context, $method_name);
            }
            if (!\is_string($method_name)) {
                $method_name_type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $this->context, $node->children['method']);
                if (!$method_name_type->canCastToUnionType(StringType::instance(false)->asUnionType())) {
                    Issue::maybeEmit(
                        $this->code_base,
                        $this->context,
                        Issue::TypeInvalidStaticMethodName,
                        $node->lineno,
                        $method_name_type
                    );
                }
                return $this->context;
            }
        }

        // Get the name of the static class being referenced
        $static_class = '';
        $class_node = $node->children['class'];
        if (!($class_node instanceof Node)) {
            $static_class = (string)$class_node;
        } elseif ($node->children['class']->kind == ast\AST_NAME) {
            $static_class = (string)$node->children['class']->children['name'];
        }

        $method = $this->getStaticMethodOrEmitIssue($node, $method_name);

        if ($method === null) {
            // Short circuit on a constructor being called statically
            // on something other than 'parent'
            if ($method_name === '__construct' && $static_class !== 'parent') {
                $this->emitConstructorWarning($node, $static_class, $method_name);
            }
            return $this->context;
        }

        try {
            if ($method_name === '__construct') {
                $this->checkNonAncestorConstructCall($node, $static_class, $method_name);
                // Even if it exists, continue on and type check the arguments passed.
            }
            // Get the method that's calling the static method
            $calling_method = null;
            if ($this->context->isInMethodScope()) {
                $calling_function_like =
                    $this->context->getFunctionLikeInScope($this->code_base);

                if ($calling_function_like instanceof Method) {
                    $calling_method = $calling_function_like;
                }
            }

            // If the method being called isn't actually static and it's
            // not a call to parent::f from f, we may be in trouble.
            if (!$method->isStatic()

                // Allow static calls to parent if we're not in a static
                // method or if it's to the overridden method
                && !(
                    (
                        'parent' === $static_class
                        || 'self' === $static_class
                        || 'static' === $static_class
                    )
                    && $this->context->isInMethodScope()
                    && (
                        $this->context->getFunctionLikeFQSEN()->getName() == $method->getFQSEN()->getName()
                        || ($calling_method && !$calling_method->isStatic())
                    )

                // Allow static calls to methods from non-static class methods
                ) && !(
                    $this->context->isInClassScope()
                    && $this->context->isInFunctionLikeScope()
                    && ($calling_method && !$calling_method->isStatic())
                // Allow static calls parent methods from closure
                ) && !(
                    $this->context->isInClassScope()
                    && $this->context->isInFunctionLikeScope()
                    && $this->context->getFunctionLikeFQSEN()->isClosure()
                )
            ) {
                $class_list = (new ContextNode(
                    $this->code_base,
                    $this->context,
                    $node->children['class']
                ))->getClassList();

                if (\count($class_list) > 0) {
                    $class = \array_values($class_list)[0];

                    $this->emitIssue(
                        Issue::StaticCallToNonStatic,
                        $node->lineno ?? 0,
                        "{$class->getFQSEN()}::{$method_name}()",
                        $method->getFileRef()->getFile(),
                        (string)$method->getFileRef()->getLineNumberStart()
                    );
                }
            }

            $this->analyzeMethodVisibility(
                $method,
                $node
            );

            // Make sure the parameters look good
            $this->analyzeCallToFunctionLike(
                $method,
                $node
            );
        } catch (IssueException $exception) {
            Issue::maybeEmitInstance(
                $this->code_base,
                $this->context,
                $exception->getIssueInstance()
            );
        } catch (Exception $_) {
            // If we can't figure out the class for this method
            // call, cry YOLO and mark every method with that
            // name with a reference.
            if (Config::get_track_references()
                && Config::getValue('dead_code_detection_prefer_false_negative')
            ) {
                foreach ($this->code_base->getMethodSetByName(
                    $method_name
                ) as $method) {
                    $method->addReference($this->context);
                }
            }

            // If we can't figure out what kind of a call
            // this is, don't worry about it
            return $this->context;
        }
        return $this->context;
    }

    /**
     * Check calling A::__construct (where A is not parent)
     * @return void
     */
    private function checkNonAncestorConstructCall(
        Node $node,
        string $static_class,
        string $method_name
    ) {
        // TODO: what about unanalyzable?
        if ($node->children['class']->kind !== ast\AST_NAME) {
            return;
        }
        $class_context_node = (new ContextNode(
            $this->code_base,
            $this->context,
            $node->children['class']
        ));
        // TODO: check for self/static/<class name of self> and warn about recursion?
        // TODO: Only allow calls to __construct from other constructors?
        $found_ancestor_constructor = false;
        if ($this->context->isInMethodScope()) {
            try {
                $possible_ancestor_type = $class_context_node->getClassUnionType();
            } catch (FQSENException $e) {
                $this->emitIssue(
                    $e instanceof EmptyFQSENException ? Issue::EmptyFQSENInCallable : Issue::InvalidFQSENInCallable,
                    $node->lineno,
                    $e->getFQSEN()
                );
                return;
            }
            // If we can determine the ancestor type, and it's an parent/ancestor class, allow the call without warning.
            // (other code should check visibility and existence and args of __construct)

            if (!$possible_ancestor_type->isEmpty()) {
                // but forbid 'self::__construct', 'static::__construct'
                $type = $this->context->getClassFQSEN()->asUnionType();
                if ($possible_ancestor_type->hasStaticType()) {
                    $this->emitIssue(
                        Issue::AccessOwnConstructor,
                        $node->lineno ?? 0,
                        $static_class
                    );
                    $found_ancestor_constructor = true;
                } elseif ($type->asExpandedTypes($this->code_base)->canCastToUnionType($possible_ancestor_type)) {
                    if ($type->canCastToUnionType($possible_ancestor_type)) {
                        $this->emitIssue(
                            Issue::AccessOwnConstructor,
                            $node->lineno ?? 0,
                            $static_class
                        );
                    }
                    $found_ancestor_constructor = true;
                }
            }
        }

        if (!$found_ancestor_constructor) {
            // TODO: new issue type?
            $this->emitConstructorWarning($node, $static_class, $method_name);
        }
    }

    /**
     * TODO: change to a different issue type in a future phan release?
     * @return void
     */
    private function emitConstructorWarning(Node $node, string $static_class, string $method_name)
    {
        $this->emitIssue(
            Issue::UndeclaredStaticMethod,
            $node->lineno ?? 0,
            "{$static_class}::{$method_name}()"
        );
    }

    /**
     * gets the static method, or emits an issue.
     * @param Node $node
     * @param string $method_name - NOTE: The caller should convert constants/class constants/etc in $node->children['method'] to a string.
     * @return ?Method
     */
    private function getStaticMethodOrEmitIssue(Node $node, string $method_name)
    {
        try {
            // Get a reference to the method being called
            $result = (new ContextNode(
                $this->code_base,
                $this->context,
                $node
            ))->getMethod($method_name, true, true);

            // This didn't throw NonClassMethodCall
            if (Config::get_strict_method_checking()) {
                $this->checkForPossibleNonObjectAndNonClassInMethod($node, $method_name);
            }

            return $result;
        } catch (IssueException $exception) {
            Issue::maybeEmitInstance(
                $this->code_base,
                $this->context,
                $exception->getIssueInstance()
            );
        } catch (Exception $e) {
            if ($e instanceof FQSENException) {
                Issue::maybeEmit(
                    $this->code_base,
                    $this->context,
                    $e instanceof EmptyFQSENException ? Issue::EmptyFQSENInClasslike : Issue::InvalidFQSENInClasslike,
                    $node->lineno,
                    $e->getFQSEN()
                );
            }
            // We already checked for NonClassMethodCall
            if (Config::get_strict_method_checking()) {
                $this->checkForPossibleNonObjectAndNonClassInMethod($node, $method_name);
            }

            // If we can't figure out the class for this method
            // call, cry YOLO and mark every method with that
            // name with a reference.
            if (Config::get_track_references()
                && Config::getValue('dead_code_detection_prefer_false_negative')
            ) {
                foreach ($this->code_base->getMethodSetByName(
                    $method_name
                ) as $method) {
                    $method->addReference($this->context);
                }
            }

            // If we can't figure out what kind of a call
            // this is, don't worry about it
        }
        return null;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitMethod(Node $node) : Context
    {
        if (!$this->context->isInFunctionLikeScope()) {
            throw new AssertionError("Must be in function-like scope to get method");
        }

        $method = $this->context->getFunctionLikeInScope($this->code_base);

        $return_type = $method->getUnionType();

        if (!($method instanceof Method)) {
            throw new AssertionError("Function found where method expected");
        }

        if ($method instanceof Method) {
            $has_interface_class = false;
            try {
                $class = $method->getClass($this->code_base);
                $has_interface_class = $class->isInterface();
            } catch (Exception $_) {
            }

            if (!$method->isAbstract()
                && !$method->isFromPHPDoc()
                && !$has_interface_class
                && !$return_type->isEmpty()
                && !$method->getHasReturn()
                && !$this->declOnlyThrows($node)
                && !$return_type->hasType(VoidType::instance(false))
                && !$return_type->hasType(NullType::instance(false))
            ) {
                $this->emitIssue(
                    Issue::TypeMissingReturn,
                    $node->lineno ?? 0,
                    (string)$method->getFQSEN(),
                    (string)$return_type
                );
            }
        }

        if ($method->getHasReturn() && $method->getIsMagicAndVoid()) {
            $this->emitIssue(
                Issue::TypeMagicVoidWithReturn,
                $node->lineno ?? 0,
                (string)$method->getFQSEN()
            );
        }

        $parameters_seen = [];
        foreach ($method->getParameterList() as $i => $parameter) {
            if (isset($parameters_seen[$parameter->getName()])) {
                $this->emitIssue(
                    Issue::ParamRedefined,
                    $node->lineno ?? 0,
                    '$' . $parameter->getName()
                );
            } else {
                $parameters_seen[$parameter->getName()] = $i;
            }
        }


        return $this->context;
    }

    /**
     * Visit a node with kind `ast\AST_FUNC_DECL`
     *
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitFuncDecl(Node $node) : Context
    {
        $method =
            $this->context->getFunctionLikeInScope($this->code_base);

        $return_type = $method->getUnionType();

        if (!$return_type->isEmpty()
            && !$method->getHasReturn()
            && !$this->declOnlyThrows($node)
            && !$return_type->hasType(VoidType::instance(false))
            && !$return_type->hasType(NullType::instance(false))
        ) {
            $this->emitIssue(
                Issue::TypeMissingReturn,
                $node->lineno ?? 0,
                (string)$method->getFQSEN(),
                (string)$return_type
            );
        }

        $parameters_seen = [];
        foreach ($method->getParameterList() as $i => $parameter) {
            if (isset($parameters_seen[$parameter->getName()])) {
                $this->emitIssue(
                    Issue::ParamRedefined,
                    $node->lineno ?? 0,
                    '$' . $parameter->getName()
                );
            } else {
                $parameters_seen[$parameter->getName()] = $i;
            }
        }

        return $this->context;
    }

    /**
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitMethodCall(Node $node) : Context
    {
        $method_name = $node->children['method'];

        if (!\is_string($method_name)) {
            if ($method_name instanceof Node) {
                $method_name = UnionTypeVisitor::anyStringLiteralForNode($this->code_base, $this->context, $method_name);
            }
            if (!\is_string($method_name)) {
                $method_name_type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $this->context, $node->children['method']);
                if (!$method_name_type->canCastToUnionType(StringType::instance(false)->asUnionType())) {
                    Issue::maybeEmit(
                        $this->code_base,
                        $this->context,
                        Issue::TypeInvalidMethodName,
                        $node->lineno,
                        $method_name_type
                    );
                }
                return $this->context;
            }
        }

        try {
            $method = (new ContextNode(
                $this->code_base,
                $this->context,
                $node
            ))->getMethod($method_name, false);
        } catch (IssueException $exception) {
            Issue::maybeEmitInstance(
                $this->code_base,
                $this->context,
                $exception->getIssueInstance()
            );
            return $this->context;
        } catch (NodeException $_) {
            // If we can't figure out the class for this method
            // call, cry YOLO and mark every method with that
            // name with a reference.
            if (Config::get_track_references()
                && Config::getValue('dead_code_detection_prefer_false_negative')
            ) {
                foreach ($this->code_base->getMethodSetByName(
                    $method_name
                ) as $method) {
                    $method->addReference($this->context);
                }
            }

            // Swallow it
            return $this->context;
        }

        // We already checked for NonClassMethodCall
        if (Config::get_strict_method_checking()) {
            $this->checkForPossibleNonObjectInMethod($node, $method_name);
        }

        $this->analyzeMethodVisibility(
            $method,
            $node
        );

        // Check the call for parameter and argument types
        $this->analyzeCallToFunctionLike(
            $method,
            $node
        );

        return $this->context;
    }

    private function checkForPossibleNonObjectInMethod(Node $node, string $method_name)
    {
        $type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $this->context, $node->children['expr'] ?? $node->children['class']);
        if ($type->containsDefiniteNonObjectType()) {
            Issue::maybeEmit(
                $this->code_base,
                $this->context,
                Issue::PossiblyNonClassMethodCall,
                $node->lineno,
                $method_name,
                $type
            );
        }
    }

    private function checkForPossibleNonObjectAndNonClassInMethod(Node $node, string $method_name)
    {
        $type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $this->context, $node->children['expr'] ?? $node->children['class']);
        if ($type->containsDefiniteNonObjectAndNonClassType()) {
            Issue::maybeEmit(
                $this->code_base,
                $this->context,
                Issue::PossiblyNonClassMethodCall,
                $node->lineno,
                $method_name,
                $type
            );
        }
    }

    /**
     * Visit a node with kind `ast\AST_DIM`
     *
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitDim(Node $node) : Context
    {
        $code_base = $this->code_base;
        $context = $this->context;
        // Check the dimension type to trigger PhanUndeclaredVariable, etc.
        /* $dim_type = */
        UnionTypeVisitor::unionTypeFromNode(
            $code_base,
            $context,
            $node->children['dim'],
            true
        );

        if ($node->flags & PhanAnnotationAdder::FLAG_IGNORE_NULLABLE_AND_UNDEF) {
            return $context;
        }
        // Check the array type to trigger TypeArraySuspicious
        try {
            /* $array_type = */
            UnionTypeVisitor::unionTypeFromNode(
                $code_base,
                $context,
                $node,
                false
            );
            // TODO: check if array_type has array but not ArrayAccess.
            // If that is true, then assert that $dim_type can cast to `int|string`
        } catch (IssueException $_) {
            // Detect this elsewhere, e.g. want to detect PhanUndeclaredVariableDim but not PhanUndeclaredVariable
        }
        return $context;
    }

    /**
     * @return bool true if the union type should skip analysis due to being the left-hand side expression of an assignment
     * We skip checks for $x['key'] being valid in expressions such as `$x['key']['key2']['key3'] = 'value';`
     * because those expressions will create $x['key'] as a side effect.
     *
     * Precondition: $parent_node->kind === ast\AST_DIM && $parent_node->children['expr'] is $node
     */
    private static function shouldSkipNestedAssignDim(array $parent_node_list) : bool
    {
        $cur_parent_node = \end($parent_node_list);
        for (;; $cur_parent_node = $prev_parent_node) {
            $prev_parent_node = \prev($parent_node_list);
            switch ($prev_parent_node->kind) {
                case ast\AST_DIM:
                    if ($prev_parent_node->children['expr'] !== $cur_parent_node) {
                        return false;
                    }
                    break;
                case ast\AST_ASSIGN:
                case ast\AST_ASSIGN_REF:
                    return $prev_parent_node->children['var'] === $cur_parent_node;
                case ast\AST_ARRAY_ELEM:
                    $prev_parent_node = \prev($parent_node_list);  // this becomes AST_ARRAY
                    break;
                case ast\AST_ARRAY:
                    break;
                default:
                    return false;
            }
        }
    }

    public function visitStaticProp(Node $node) : Context
    {
        return $this->analyzeProp($node, true);
    }

    public function visitProp(Node $node) : Context
    {
        return $this->analyzeProp($node, false);
    }

    /**
     * Default visitor for node kinds that do not have
     * an overriding method
     *
     * @param Node $node (@phan-unused-param)
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitClone(Node $node) : Context
    {
        $type = UnionTypeVisitor::unionTypeFromNode(
            $this->code_base,
            $this->context,
            $node->children['expr'],
            true
        );
        if (!$type->isEmpty() && !$type->hasPossiblyObjectTypes()) {
            $this->emitIssue(
                Issue::TypeInvalidCloneNotObject,
                $node->children['expr']->lineno ?? $node->lineno,
                $type
            );
        }
        return $this->context;
    }

    /**
     * Analyze a node with kind `ast\AST_PROP` or `ast\AST_STATIC_PROP`
     *
     * @param Node $node
     * A node of the type indicated by the method name that we'd
     * like to figure out the type that it produces.
     *
     * @param bool $is_static
     * True if fetching a static property.
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function analyzeProp(Node $node, bool $is_static) : Context
    {
        $exception_or_null = null;

        try {
            $property = (new ContextNode(
                $this->code_base,
                $this->context,
                $node
            ))->getProperty($is_static);

            // Mark that this property has been referenced from
            // this context
            if (Config::get_track_references()) {
                $this->trackPropertyReference($property, $node);
            }
        } catch (IssueException $exception) {
            // We'll check out some reasons it might not exist
            // before logging the issue
            $exception_or_null = $exception;
        } catch (NodeException $exception) {
            $exception_or_null = $exception;
        } catch (Exception $_) {
            // Swallow any exceptions. We'll catch it later.
        }

        if (isset($property)) {
            $this->analyzeNoOp($node, Issue::NoopProperty);
        } else {
            $expr_or_class_node = $node->children['expr'] ?? $node->children['class'];
            if ($expr_or_class_node === null) {
                throw new AssertionError(
                    "Property nodes must either have an expression or class"
                );
            }

            $class_list = [];
            try {
                // Get the set of classes that are being referenced
                $class_list = (new ContextNode(
                    $this->code_base,
                    $this->context,
                    $expr_or_class_node
                ))->getClassList(true);
            } catch (IssueException $exception) {
                Issue::maybeEmitInstance(
                    $this->code_base,
                    $this->context,
                    $exception->getIssueInstance()
                );
            }

            if (!$is_static) {
                // Find out of any of them have a __get magic method
                // (Only check if looking for instance properties)
                $has_getter = $this->hasGetter($class_list);

                // If they don't, then analyze for No-ops.
                if (!$has_getter) {
                    $this->analyzeNoOp($node, Issue::NoopProperty);

                    if ($exception_or_null instanceof IssueException) {
                        Issue::maybeEmitInstance(
                            $this->code_base,
                            $this->context,
                            $exception_or_null->getIssueInstance()
                        );
                    }
                }
            } else {
                if ($exception_or_null instanceof IssueException) {
                    Issue::maybeEmitInstance(
                        $this->code_base,
                        $this->context,
                        $exception_or_null->getIssueInstance()
                    );
                }
            }
        }

        return $this->context;
    }

    /** @param Clazz[] $class_list */
    private function hasGetter(array $class_list) : bool
    {
        foreach ($class_list as $class) {
            if ($class->hasGetMethod($this->code_base)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return void
     */
    private function trackPropertyReference(Property $property, Node $node)
    {
        $property->addReference($this->context);
        if (!$property->hasReadReference() && !$this->isAssignmentOrNestedAssignment($node)) {
            $property->setHasReadReference();
        }
        if (!$property->hasWriteReference() && $this->isAssignmentOrNestedAssignmentOrModification($node)) {
            $property->setHasWriteReference();
        }
    }

    private function isAssignmentOrNestedAssignment(Node $node) : bool
    {
        $parent_node_list = $this->parent_node_list;
        $parent_node = \end($parent_node_list);
        $parent_kind = $parent_node->kind;
        // E.g. analyzing [$x] in [$x] = expr()
        while ($parent_kind === ast\AST_ARRAY_ELEM) {
            if ($parent_node->children['value'] !== $node) {
                // e.g. analyzing `$v = [$x => $y];` for $x
                return false;
            }
            \array_pop($parent_node_list);  // pop AST_ARRAY_ELEM
            $node = \array_pop($parent_node_list);  // AST_ARRAY
            $parent_node = \array_pop($parent_node_list);
            $parent_kind = $parent_node->kind;
        }
        if ($parent_kind === ast\AST_DIM) {
            return $parent_node->children['expr'] === $node && $this->shouldSkipNestedAssignDim($parent_node_list);
        } elseif ($parent_kind === ast\AST_ASSIGN || $parent_kind === ast\AST_ASSIGN_REF || $parent_kind === ast\AST_ASSIGN_OP) {
            return $parent_node->children['var'] === $node;
        }
        return false;
    }

    // An incomplete list of known parent node kinds that simultaneously read and write the given expression
    // TODO: ASSIGN_OP?
    const _READ_AND_WRITE_KINDS = [
        ast\AST_PRE_INC,
        ast\AST_PRE_DEC,
        ast\AST_POST_INC,
        ast\AST_POST_DEC,
    ];

    private function isAssignmentOrNestedAssignmentOrModification(Node $node) : bool
    {
        $parent_node_list = $this->parent_node_list;
        $parent_node = \end($parent_node_list);
        $parent_kind = $parent_node->kind;
        // E.g. analyzing [$x] in [$x] = expr()
        while ($parent_kind === ast\AST_ARRAY_ELEM) {
            if ($parent_node->children['value'] !== $node) {
                // e.g. analyzing `$v = [$x => $y];` for $x
                return false;
            }
            \array_pop($parent_node_list);  // pop AST_ARRAY_ELEM
            $node = \array_pop($parent_node_list);  // AST_ARRAY
            $parent_node = \array_pop($parent_node_list);
            $parent_kind = $parent_node->kind;
        }
        if ($parent_kind === ast\AST_DIM) {
            return $parent_node->children['expr'] === $node && self::shouldSkipNestedAssignDim($parent_node_list);
        } elseif ($parent_kind === ast\AST_ASSIGN || $parent_kind === ast\AST_ASSIGN_REF || $parent_kind === ast\AST_ASSIGN_OP) {
            return $parent_node->children['var'] === $node;
        } else {
            return \in_array($parent_kind, self::_READ_AND_WRITE_KINDS, true);
        }
    }

    /**
     * Analyze whether a method is callable
     *
     * @param Method $method
     * @param Node $node
     *
     * @return void
     */
    private function analyzeMethodVisibility(
        Method $method,
        Node $node
    ) {
        if ($method->isPrivate()
            && (
                !$this->context->isInClassScope()
                || $this->context->getClassFQSEN() != $method->getDefiningClassFQSEN()
            )
        ) {
            $has_call_magic_method = !$method->isStatic()
                && $method->getDefiningClass($this->code_base)->hasMethodWithName($this->code_base, '__call');

            $this->emitIssue(
                $has_call_magic_method ?
                    Issue::AccessMethodPrivateWithCallMagicMethod : Issue::AccessMethodPrivate,
                $node->lineno ?? 0,
                (string)$method->getFQSEN(),
                $method->getFileRef()->getFile(),
                (string)$method->getFileRef()->getLineNumberStart()
            );
        } elseif ($method->isProtected() && !$this->canAccessProtectedMethodFromContext($method)) {
            $has_call_magic_method = !$method->isStatic()
                && $method->getDefiningClass($this->code_base)->hasMethodWithName($this->code_base, '__call');

            $this->emitIssue(
                $has_call_magic_method ?
                    Issue::AccessMethodProtectedWithCallMagicMethod : Issue::AccessMethodProtected,
                $node->lineno ?? 0,
                (string)$method->getFQSEN(),
                $method->getFileRef()->getFile(),
                (string)$method->getFileRef()->getLineNumberStart()
            );
        }
    }

    private function canAccessProtectedMethodFromContext(Method $method) : bool
    {
        $context = $this->context;
        if (!$context->isInClassScope()) {
            return false;
        }
        $class_fqsen = $context->getClassFQSEN();
        $class_fqsen_type = $class_fqsen->asType();
        $method_class_fqsen_type = $method->getClassFQSEN()->asType();
        if ($class_fqsen_type->canCastToType($method_class_fqsen_type)) {
            return true;
        }
        $method_defining_class_fqsen = $method->getDefiningClassFQSEN();
        if ($class_fqsen === $method_defining_class_fqsen) {
            return true;
        }
        $method_defining_class_fqsen_type = $method_defining_class_fqsen->asType();
        if ($class_fqsen_type->isSubclassOf($this->code_base, $method_defining_class_fqsen_type)) {
            return true;
        }
        if ($method_defining_class_fqsen_type->isSubclassOf($this->code_base, $class_fqsen_type)) {
            return true;
        }
        return false;
    }

    /**
     * Analyze the parameters and arguments for a call
     * to the given method or function
     *
     * @param FunctionInterface $method
     * @param Node $node
     *
     * @return void
     */
    private function analyzeCallToFunctionLike(
        FunctionInterface $method,
        Node $node
    ) {
        $code_base = $this->code_base;
        $context = $this->context;

        $method->addReference($context);

        // Create variables for any pass-by-reference
        // parameters
        $argument_list = $node->children['args']->children;
        foreach ($argument_list as $i => $argument) {
            if (!$argument instanceof Node) {
                continue;
            }

            $parameter = $method->getParameterForCaller($i);
            if (!$parameter) {
                continue;
            }

            // If pass-by-reference, make sure the variable exists
            // or create it if it doesn't.
            if ($parameter->isPassByReference()) {
                $this->createPassByReferenceArgumentInCall($argument, $parameter, $method->getRealParameterForCaller($i));
            }
        }

        // Confirm the argument types are clean
        ArgumentType::analyze(
            $method,
            $node,
            $context,
            $code_base
        );

        // Take another pass over pass-by-reference parameters
        // and assign types to passed in variables
        foreach ($argument_list as $i => $argument) {
            if (!$argument instanceof Node) {
                continue;
            }
            $parameter = $method->getParameterForCaller($i);

            if (!$parameter) {
                continue;
            }

            $kind = $argument->kind;
            if ($kind === ast\AST_CLOSURE) {
                if (Config::get_track_references()) {
                    $this->trackReferenceToClosure($argument);
                }
            }

            // If the parameter is pass-by-reference and we're
            // passing a variable in, see if we should pass
            // the parameter and variable types to each other
            if ($parameter->isPassByReference()) {
                $this->analyzePassByReferenceArgument(
                    $code_base,
                    $context,
                    $argument,
                    $argument_list,
                    $method,
                    $parameter,
                    $method->getRealParameterForCaller($i)
                );
            }
        }

        // If we're in quick mode, don't retest methods based on
        // parameter types passed in
        if (Config::get_quick_mode()) {
            return;
        }

        // Don't re-analyze recursive methods. That doesn't go
        // well.
        if ($context->isInFunctionLikeScope()
            && $method->getFQSEN() === $context->getFunctionLikeFQSEN()
        ) {
            $this->checkForInfiniteRecursion($node, $method);
            return;
        }

        if (!$method->needsRecursiveAnalysis()) {
            return;
        }

        // Re-analyze the method with the types of the arguments
        // being passed in.
        $this->analyzeMethodWithArgumentTypes(
            $node->children['args'],
            $method
        );
    }

    /**
     * @param Parameter $parameter the parameter types inferred from combination of real and union type
     *
     * @param ?Parameter $real_parameter the real parameter type from the type signature
     */
    private function createPassByReferenceArgumentInCall(Node $argument, Parameter $parameter, $real_parameter)
    {
        if ($argument->kind == ast\AST_VAR) {
            // We don't do anything with the new variable; just create it
            // if it doesn't exist
            try {
                (new ContextNode(
                    $this->code_base,
                    $this->context,
                    $argument
                ))->getOrCreateVariableForReferenceParameter($parameter, $real_parameter);
            } catch (NodeException $_) {
                return;
            }
        } elseif ($argument->kind == ast\AST_STATIC_PROP
            || $argument->kind == ast\AST_PROP
        ) {
            $property_name = $argument->children['prop'];

            if (\is_string($property_name)) {
                // We don't do anything with it; just create it
                // if it doesn't exist
                try {
                    $property = (new ContextNode(
                        $this->code_base,
                        $this->context,
                        $argument
                    ))->getOrCreateProperty($argument->children['prop'], $argument->kind == ast\AST_STATIC_PROP);
                    $property->setHasWriteReference();
                } catch (IssueException $exception) {
                    Issue::maybeEmitInstance(
                        $this->code_base,
                        $this->context,
                        $exception->getIssueInstance()
                    );
                } catch (Exception $_) {
                    // If we can't figure out what kind of a call
                    // this is, don't worry about it
                }
            } else {
                // This is stuff like `Class->$foo`. I'm ignoring
                // it.
            }
        }
    }

    /**
     * @param Parameter $parameter the parameter types inferred from combination of real and union type
     *
     * @param ?Parameter $real_parameter the real parameter type from the type signature
     *
     * @return void
     */
    private function analyzePassByReferenceArgument(
        CodeBase $code_base,
        Context $context,
        Node $argument,
        array $argument_list,
        FunctionInterface $method,
        Parameter $parameter,
        $real_parameter
    ) {
        $variable = null;
        $kind = $argument->kind;
        if ($kind === ast\AST_VAR) {
            try {
                $variable = (new ContextNode(
                    $code_base,
                    $context,
                    $argument
                ))->getOrCreateVariableForReferenceParameter($parameter, $real_parameter);
            } catch (NodeException $_) {
                // E.g. `function_accepting_reference(${$varName})` - Phan can't analyze outer type of ${$varName}
                return;
            }
        } elseif ($kind === ast\AST_STATIC_PROP
            || $kind === ast\AST_PROP
        ) {
            $property_name = $argument->children['prop'];

            if (\is_string($property_name)) {
                // We don't do anything with it; just create it
                // if it doesn't exist
                try {
                    $variable = (new ContextNode(
                        $code_base,
                        $context,
                        $argument
                    ))->getOrCreateProperty($argument->children['prop'], $argument->kind == ast\AST_STATIC_PROP);
                    $variable->addReference($context);
                } catch (IssueException $exception) {
                    Issue::maybeEmitInstance(
                        $code_base,
                        $context,
                        $exception->getIssueInstance()
                    );
                } catch (Exception $_) {
                    // If we can't figure out what kind of a call
                    // this is, don't worry about it
                }
            } else {
                // This is stuff like `Class->$foo`. I'm ignoring
                // it.
            }
        }

        if ($variable) {
            switch ($parameter->getReferenceType()) {
                case Parameter::REFERENCE_WRITE_ONLY:
                    $this->analyzeWriteOnlyReference($code_base, $context, $method, $variable, $argument_list, $parameter);
                    break;
                case Parameter::REFERENCE_READ_WRITE:
                    $reference_parameter_type = $parameter->getNonVariadicUnionType();
                    $variable_type = $variable->getUnionType();
                    if ($variable_type->isEmpty()) {
                        // if Phan doesn't know the variable type,
                        // then guess that the variable is the type of the reference
                        // when analyzing the following statements.
                        $variable->setUnionType(
                            $reference_parameter_type
                        );
                    } elseif (!$variable_type->canCastToUnionType($reference_parameter_type)) {
                        // Phan already warned about incompatible types.
                        // But analyze the following statements as if it could have been the type expected,
                        // to reduce false positives.
                        $variable->setUnionType($variable->getUnionType()->withUnionType(
                            $reference_parameter_type
                        ));
                    }
                    // don't modify - assume the function takes the same type in that it returns,
                    // and we want to preserve generic array types for sorting functions (May change later on)
                    // TODO: Check type compatibility earlier, and don't modify?
                    break;
                case Parameter::REFERENCE_DEFAULT:
                default:
                    $reference_parameter_type = $parameter->getNonVariadicUnionType();
                    // We have no idea what type of reference this is.
                    // Probably user defined code.
                    $variable->setUnionType($variable->getUnionType()->withUnionType(
                        $reference_parameter_type
                    ));
                    break;
            }
        }
    }

    /**
     * @param Property|Variable $variable
     */
    private function analyzeWriteOnlyReference(
        CodeBase $code_base,
        Context $context,
        FunctionInterface $method,
        $variable,
        array $argument_list,
        Parameter $parameter
    ) {
        switch ($method->getFQSEN()->__toString()) {
            case '\preg_match':
                $variable->setUnionType(
                    RegexAnalyzer::getPregMatchUnionType($code_base, $context, $argument_list)
                );
                return;
            case '\preg_match_all':
                $variable->setUnionType(
                    RegexAnalyzer::getPregMatchAllUnionType($code_base, $context, $argument_list)
                );
                return;
            default:
                $reference_parameter_type = $parameter->getNonVariadicUnionType();

                // The previous value is being ignored, and being replaced.
                $variable->setUnionType(
                    $reference_parameter_type
                );
        }
    }

    /**
     * @return void
     */
    private function trackReferenceToClosure(Node $argument)
    {
        try {
            $inner_context = $this->context->withLineNumberStart($argument->lineno ?? 0);
            $method = (new ContextNode(
                $this->code_base,
                $inner_context,
                $argument
            ))->getClosure();

            $method->addReference($inner_context);
        } catch (Exception $_) {
            // Swallow it
        }
    }

    /**
     * Replace the method's parameter types with the argument
     * types and re-analyze the method.
     *
     * This is used when analyzing callbacks and closures, e.g. in array_map.
     *
     * @param array<int,UnionType> $argument_types
     * An AST node listing the arguments
     *
     * @param FunctionInterface $method
     * The method or function being called
     *
     * @return void
     *
     * @see analyzeMethodWithArgumentTypes (Which takes AST nodes)
     */
    public function analyzeCallableWithArgumentTypes(
        array $argument_types,
        FunctionInterface $method
    ) {
        if (!$method->needsRecursiveAnalysis()) {
            return;
        }

        // Don't re-analyze recursive methods. That doesn't go well.
        if ($this->context->isInFunctionLikeScope()
            && $method->getFQSEN() === $this->context->getFunctionLikeFQSEN()
        ) {
            return;
        }

        $original_method_scope = $method->getInternalScope();
        $method->setInternalScope(clone($original_method_scope));
        try {
            // Even though we don't modify the parameter list, we still need to know the types
            // -- as an optimization, we don't run quick mode again if the types didn't change?
            $parameter_list = \array_map(/** @return Parameter */ function (Parameter $parameter) {
                return clone($parameter);
            }, $method->getParameterList());

            foreach ($parameter_list as $i => $parameter_clone) {
                if (!isset($argument_types[$i]) && $parameter_clone->hasDefaultValue()) {
                    $parameter_type = $parameter_clone->getDefaultValueType();
                    if ($parameter_type->isType(NullType::instance(false))) {
                        // Treat a parameter default of null the same way as passing null to that parameter
                        // (Add null to the list of possibilities)
                        $parameter_clone->addUnionType($parameter_type);
                    } else {
                        // For other types (E.g. string), just replace the union type.
                        $parameter_clone->setUnionType($parameter_type);
                    }
                }

                // Add the parameter to the scope
                $method->getInternalScope()->addVariable(
                    $parameter_clone->asNonVariadic()
                );

                // If there's no parameter at that offset, we may be in
                // a ParamTooMany situation. That is caught elsewhere.
                if (!isset($argument_types[$i])
                    || !$parameter_clone->getNonVariadicUnionType()->isEmpty()
                ) {
                    continue;
                }

                $this->updateParameterTypeByArgument(
                    $method,
                    $parameter_clone,
                    null,  // TODO: Can array_map/array_filter accept closures with references? Consider warning?
                    $argument_types,
                    $parameter_list,
                    $i
                );
            }
            foreach ($parameter_list as $parameter_clone) {
                if ($parameter_clone->isVariadic()) {
                    // We're using this parameter clone to analyze the **inside** of the method, it's never seen on the outside.
                    // Convert it immediately.
                    // TODO: Add tests of variadic references, fix those if necessary.
                    $method->getInternalScope()->addVariable(
                        $parameter_clone->cloneAsNonVariadic()
                    );
                }
            }

            // Now that we know something about the parameters used
            // to call the method, we can reanalyze the method with
            // the types of the parameter
            $method->analyzeWithNewParams($method->getContext(), $this->code_base, $parameter_list);
        } finally {
            $method->setInternalScope($original_method_scope);
        }
    }

    /**
     * Replace the method's parameter types with the argument
     * types and re-analyze the method.
     *
     * @param Node $argument_list_node
     * An AST node listing the arguments
     *
     * @param FunctionInterface $method
     * The method or function being called
     * Precondition: $method->needsRecursiveAnalysis() === false
     *
     * @return void
     *
     * TODO: deduplicate code.
     */
    private function analyzeMethodWithArgumentTypes(
        Node $argument_list_node,
        FunctionInterface $method
    ) {
        $original_method_scope = $method->getInternalScope();
        $method->setInternalScope(clone($original_method_scope));
        $method_context = $method->getContext();

        try {
            // Even though we don't modify the parameter list, we still need to know the types
            // -- as an optimization, we don't run quick mode again if the types didn't change?
            $parameter_list = \array_map(function (Parameter $parameter) : Parameter {
                return $parameter->cloneAsNonVariadic();
            }, $method->getParameterList());

            // always resolve all arguments outside of quick mode to detect undefined variables, other problems in call arguments.
            // Fixes https://github.com/phan/phan/issues/583
            $argument_types = [];
            foreach ($argument_list_node->children as $i => $argument) {
                // Determine the type of the argument at position $i
                $argument_types[$i] = UnionTypeVisitor::unionTypeFromNode(
                    $this->code_base,
                    $this->context,
                    $argument,
                    true
                );
            }

            foreach ($parameter_list as $i => $parameter_clone) {
                $argument = $argument_list_node->children[$i] ?? null;

                if (!$argument
                    && $parameter_clone->hasDefaultValue()
                ) {
                    $parameter_type = $parameter_clone->getDefaultValueType();
                    if ($parameter_type->isType(NullType::instance(false))) {
                        // Treat a parameter default of null the same way as passing null to that parameter
                        // (Add null to the list of possibilities)
                        $parameter_clone->addUnionType($parameter_type);
                    } else {
                        // For other types (E.g. string), just replace the union type.
                        $parameter_clone->setUnionType($parameter_type);
                    }
                }

                // Add the parameter to the scope
                $method->getInternalScope()->addVariable(
                    $parameter_clone
                );

                // If there's no parameter at that offset, we may be in
                // a ParamTooMany situation. That is caught elsewhere.
                if (!$argument
                    || !$parameter_clone->getUnionType()->isEmpty()
                ) {
                    continue;
                }

                $this->updateParameterTypeByArgument(
                    $method,
                    $parameter_clone,
                    $argument,
                    $argument_types,
                    $parameter_list,
                    $i
                );
            }
            foreach ($parameter_list as $parameter_clone) {
                if ($parameter_clone->isVariadic()) {
                    // We're using this parameter clone to analyze the **inside** of the method, it's never seen on the outside.
                    // Convert it immediately.
                    // TODO: Add tests of variadic references, fix those if necessary.
                    $method->getInternalScope()->addVariable(
                        $parameter_clone->cloneAsNonVariadic()
                    );
                }
            }

            // Now that we know something about the parameters used
            // to call the method, we can reanalyze the method with
            // the types of the parameter
            $method->analyzeWithNewParams($method_context, $this->code_base, $parameter_list);
        } finally {
            $method->setInternalScope($original_method_scope);
        }
    }

    /**
     * Check if $argument_list_node calling itself is likely to be a case of infinite recursion.
     * This is based on heuristics, and will not catch all cases.
     */
    private function checkForInfiniteRecursion(Node $node, FunctionInterface $method)
    {
        $argument_list_node = $node->children['args'];
        if ($node->kind === ast\AST_METHOD_CALL) {
            $expr = $node->children['expr'];
            if (($expr->kind ?? null) !== ast\AST_VAR || $expr->children['name'] !== 'this') {
                return;
            }
        }
        $nearest_function_like = null;
        foreach ($this->parent_node_list as $c) {
            if (\in_array($c->kind, [ast\AST_FUNC_DECL, ast\AST_METHOD, ast\AST_CLOSURE], true)) {
                $nearest_function_like = $c;
            }
        }
        if (!$nearest_function_like) {
            return;
        }
        if (ReachabilityChecker::willUnconditionallyBeReached($nearest_function_like->children['stmts'], $argument_list_node)) {
            $this->emitIssue(
                Issue::InfiniteRecursion,
                $argument_list_node->lineno,
                $method->getNameForIssue()
            );
        }
    }

    /**
     * @param FunctionInterface $method
     * The method that we're updating parameter types for
     *
     * @param Parameter $parameter
     * The parameter that we're updating
     *
     * @param Node|mixed $argument
     * The argument whose type we'd like to replace the
     * parameter type with.
     *
     * @param array<int,UnionType> $argument_types
     * The type of arguments
     *
     * @param array<int,Parameter> &$parameter_list
     * The parameter list - types are modified by reference
     *
     * @param int $parameter_offset
     * The offset of the parameter on the method's
     * signature.
     *
     * @return void
     */
    private function updateParameterTypeByArgument(
        FunctionInterface $method,
        Parameter $parameter,
        $argument,
        array $argument_types,
        array &$parameter_list,
        int $parameter_offset
    ) {
        $argument_type = $argument_types[$parameter_offset];
        if ($parameter->isVariadic()) {
            for ($i = $parameter_offset + 1; $i < \count($argument_types); $i++) {
                $argument_type = $argument_type->withUnionType($argument_types[$i]);
            }
        }
        // Then set the new type on that parameter based
        // on the argument's type. We'll use this to
        // retest the method with the passed in types
        // TODO: if $argument_type is non-empty and !isType(NullType), instead use setUnionType?

        // For https://github.com/phan/phan/issues/1525 : Collapse array shapes into generic arrays before recursively analyzing a method.
        if (!$parameter->isCloneOfVariadic()) {
            $parameter->addUnionType(
                $argument_type->withFlattenedArrayShapeOrLiteralTypeInstances()
            );
        } else {
            $parameter->addUnionType(
                $argument_type->withFlattenedArrayShapeOrLiteralTypeInstances()->asGenericArrayTypes(GenericArrayType::KEY_INT)
            );
        }

        // If we're passing by reference, get the variable
        // we're dealing with wrapped up and shoved into
        // the scope of the method
        if (!$parameter->isPassByReference()) {
            // Overwrite the method's variable representation
            // of the parameter with the parameter with the
            // new type
            $method->getInternalScope()->addVariable(
                $parameter
            );

            return;
        }

        // At this point we're dealing with a pass-by-reference
        // parameter.

        // For now, give up and work on it later.
        //
        // TODO (Issue #376): It's possible to have a
        // parameter `&...$args`. Analyzing that is going to
        // be a problem. Is it possible to create
        // `PassByReferenceVariableCollection extends Variable`
        // or something similar?
        if ($parameter->isVariadic()) {
            return;
        }

        if (!$argument instanceof Node) {
            return;
        }

        $variable = null;
        if ($argument->kind == ast\AST_VAR) {
            try {
                $variable = (new ContextNode(
                    $this->code_base,
                    $this->context,
                    $argument
                ))->getOrCreateVariableForReferenceParameter($parameter, $method->getRealParameterForCaller($parameter_offset));
            } catch (NodeException $_) {
                // Could not figure out the node name
                return;
            }
        } elseif ($argument->kind == ast\AST_STATIC_PROP) {
            try {
                $variable = (new ContextNode(
                    $this->code_base,
                    $this->context,
                    $argument
                ))->getProperty(
                    true
                );
            } catch (IssueException $_) {
                // Hopefully caught elsewhere
            } catch (NodeException $_) {
                // Hopefully caught elsewhere
            }
        }

        // If we couldn't find a variable, give up
        if (!$variable) {
            return;
        }

        $pass_by_reference_variable =
            new PassByReferenceVariable(
                $parameter,
                $variable
            );

        // Add it to the (cloned) scope of the function wrapped
        // in a way that makes it addressable as the
        // parameter its mimicking
        $method->getInternalScope()->addVariable(
            $pass_by_reference_variable
        );
        $parameter_list[$parameter_offset] = $pass_by_reference_variable;
    }

    /**
     * @param Node $node
     * A node to check to see if it's a no-op
     *
     * @param string $issue_type
     * A message to emit if it's a no-op
     *
     * @return void
     */
    private function analyzeNoOp(Node $node, string $issue_type)
    {
        if ((\end($this->parent_node_list)->kind ?? null) === ast\AST_STMT_LIST) {
            $this->emitIssue(
                $issue_type,
                $node->lineno
            );
        }
    }

    const LOOP_SCOPE_KINDS = [
        ast\AST_FOR => true,
        ast\AST_FOREACH => true,
        ast\AST_WHILE => true,
        ast\AST_DO_WHILE => true,
        ast\AST_SWITCH => true,
    ];

    /**
     * Analyzes a `break;` or `break N;` statement.
     * Checks if there are enough loops to break out of.
     */
    public function visitBreak(Node $node) : Context
    {
        $depth = $node->children['depth'] ?? 1;
        if (!\is_int($depth)) {
            return $this->context;
        }
        foreach ($this->parent_node_list as $iter_node) {
            if (\array_key_exists($iter_node->kind, self::LOOP_SCOPE_KINDS)) {
                $depth--;
                if ($depth <= 0) {
                    return $this->context;
                }
            }
        }
        $this->warnBreakOrContinueWithoutLoop($node);
        return $this->context;
    }

    /**
     * Analyzes a `continue;` or `continue N;` statement.
     * Checks for http://php.net/manual/en/migration73.incompatible.php#migration73.incompatible.core.continue-targeting-switch
     * and similar issues.
     */
    public function visitContinue(Node $node) : Context
    {
        $nodes = $this->parent_node_list;
        $depth = $node->children['depth'] ?? 1;
        if (!\is_int($depth)) {
            return $this->context;
        }
        for ($iter_node = \end($nodes); $iter_node instanceof Node; $iter_node = \prev($nodes)) {
            switch ($iter_node->kind) {
                case ast\AST_FOR:
                case ast\AST_FOREACH:
                case ast\AST_WHILE:
                case ast\AST_DO_WHILE:
                    $depth--;
                    if ($depth <= 0) {
                        return $this->context;
                    }
                    break;
                case ast\AST_SWITCH:
                    $depth--;
                    if ($depth <= 0) {
                        $this->emitIssue(
                            Issue::ContinueTargetingSwitch,
                            $node->lineno
                        );
                        return $this->context;
                    }
                    break;
            }
        }
        $this->warnBreakOrContinueWithoutLoop($node);
        return $this->context;
    }

    /**
     * @return void
     */
    private function warnBreakOrContinueWithoutLoop(Node $node)
    {
        $depth = $node->children['depth'] ?? 1;
        $name = $node->kind === ast\AST_BREAK ? 'break' : 'continue';
        if ($depth !== 1) {
            $this->emitIssue(
                Issue::ContinueOrBreakTooManyLevels,
                $node->lineno,
                $name,
                $depth
            );
            return;
        }
        $this->emitIssue(
            Issue::ContinueOrBreakNotInLoop,
            $node->lineno,
            $name
        );
    }

    /**
     * @param Node $node
     * A decl to check to see if it's only effect
     * is the throw an exception
     *
     * @return bool
     * True when the decl can only throw an exception or return or exit()
     */
    private function declOnlyThrows(Node $node) : bool
    {
        // Work around fallback parser generating methods without statements list.
        // Otherwise, 'stmts' would always be a Node due to preconditions.
        $stmts_node = $node->children['stmts'];
        return $stmts_node instanceof Node && BlockExitStatusChecker::willUnconditionallyThrowOrReturn($stmts_node);
    }
}
