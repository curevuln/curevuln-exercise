<?php declare(strict_types=1);

namespace Phan;

use AssertionError;
use InvalidArgumentException;
use Phan\Language\Context;
use Phan\Language\Element\TypedElement;
use Phan\Language\Element\UnaddressableTypedElement;
use Phan\Language\FQSEN;
use Phan\Language\Type;
use Phan\Language\UnionType;
use Phan\Plugin\ConfigPluginSet;

/**
 * An issue emitted during analysis.
 * @phan-file-suppress PhanPluginDescriptionlessCommentOnPublicMethod
 * @SuppressWarnings(PHPMD.ConstantNamingConventions) these constant names are deliberately used to match the values
 */
class Issue
{
    // phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
    // this is deliberate for issue names
    // Issue::CATEGORY_SYNTAX
    const SyntaxError                    = 'PhanSyntaxError';
    const InvalidConstantExpression      = 'PhanInvalidConstantExpression';
    const InvalidNode                    = 'PhanInvalidNode';
    const InvalidWriteToTemporaryExpression = 'PhanInvalidWriteToTemporaryExpression';
    const InvalidTraitUse                = 'PhanInvalidTraitUse';
    const ContinueTargetingSwitch        = 'PhanContinueTargetingSwitch';
    const ContinueOrBreakNotInLoop       = 'PhanContinueOrBreakNotInLoop';
    const ContinueOrBreakTooManyLevels   = 'PhanContinueOrBreakTooManyLevels';

    // Issue::CATEGORY_UNDEFINED
    const AmbiguousTraitAliasSource = 'PhanAmbiguousTraitAliasSource';
    const ClassContainsAbstractMethodInternal = 'PhanClassContainsAbstractMethodInternal';
    const ClassContainsAbstractMethod = 'PhanClassContainsAbstractMethod';
    const EmptyFile                 = 'PhanEmptyFile';
    const MissingRequireFile        = 'PhanMissingRequireFile';
    const InvalidRequireFile        = 'PhanInvalidRequireFile';
    const ParentlessClass           = 'PhanParentlessClass';
    const RequiredTraitNotAdded     = 'PhanRequiredTraitNotAdded';
    const TraitParentReference      = 'PhanTraitParentReference';
    const UndeclaredAliasedMethodOfTrait = 'PhanUndeclaredAliasedMethodOfTrait';
    const UndeclaredClass           = 'PhanUndeclaredClass';
    const UndeclaredClassAliasOriginal = 'PhanUndeclaredClassAliasOriginal';
    const UndeclaredClassCatch      = 'PhanUndeclaredClassCatch';
    const UndeclaredClassConstant   = 'PhanUndeclaredClassConstant';
    const UndeclaredClassInstanceof = 'PhanUndeclaredClassInstanceof';
    const UndeclaredClassMethod     = 'PhanUndeclaredClassMethod';
    const UndeclaredClassProperty   = 'PhanUndeclaredClassProperty';
    const UndeclaredClassReference  = 'PhanUndeclaredClassReference';
    const UndeclaredClassStaticProperty = 'PhanUndeclaredClassStaticProperty';
    const UndeclaredClosureScope    = 'PhanUndeclaredClosureScope';
    const UndeclaredConstant        = 'PhanUndeclaredConstant';
    const UndeclaredMagicConstant   = 'PhanUndeclaredMagicConstant';
    const UndeclaredExtendedClass   = 'PhanUndeclaredExtendedClass';
    const UndeclaredFunction        = 'PhanUndeclaredFunction';
    const UndeclaredInterface       = 'PhanUndeclaredInterface';
    const UndeclaredMethod          = 'PhanUndeclaredMethod';
    const UndeclaredProperty        = 'PhanUndeclaredProperty';
    const UndeclaredStaticMethod    = 'PhanUndeclaredStaticMethod';
    const UndeclaredStaticProperty  = 'PhanUndeclaredStaticProperty';
    const UndeclaredTrait           = 'PhanUndeclaredTrait';
    const UndeclaredTypeParameter   = 'PhanUndeclaredTypeParameter';
    const UndeclaredTypeReturnType  = 'PhanUndeclaredTypeReturnType';
    const UndeclaredTypeProperty    = 'PhanUndeclaredTypeProperty';
    const UndeclaredTypeThrowsType  = 'PhanUndeclaredTypeThrowsType';
    const UndeclaredVariable        = 'PhanUndeclaredVariable';
    const UndeclaredVariableDim     = 'PhanUndeclaredVariableDim';
    const UndeclaredVariableAssignOp = 'PhanUndeclaredVariableAssignOp';
    const UndeclaredClassInCallable = 'PhanUndeclaredClassInCallable';
    const UndeclaredStaticMethodInCallable = 'PhanUndeclaredStaticMethodInCallable';
    const UndeclaredFunctionInCallable = 'PhanUndeclaredFunctionInCallable';
    const UndeclaredMethodInCallable = 'PhanUndeclaredMethodInCallable';
    const UndeclaredInvokeInCallable = 'PhanUndeclaredInvokeInCallable';
    const EmptyFQSENInCallable      = 'PhanEmptyFQSENInCallable';
    const InvalidFQSENInCallable    = 'PhanInvalidFQSENInCallable';
    const EmptyFQSENInClasslike     = 'PhanEmptyFQSENInClasslike';
    const InvalidFQSENInClasslike     = 'PhanInvalidFQSENInClasslike';

    // Issue::CATEGORY_TYPE
    const NonClassMethodCall        = 'PhanNonClassMethodCall';
    const PossiblyNonClassMethodCall = 'PhanPossiblyNonClassMethodCall';
    const TypeArrayOperator         = 'PhanTypeArrayOperator';
    const TypeInvalidBitwiseBinaryOperator = 'PhanTypeInvalidBitwiseBinaryOperator';
    const TypeMismatchBitwiseBinaryOperands = 'PhanTypeMismatchBitwiseBinaryOperands';
    const TypeArraySuspicious       = 'PhanTypeArraySuspicious';
    const TypeArrayUnsetSuspicious  = 'PhanTypeArrayUnsetSuspicious';
    const TypeArraySuspiciousNullable = 'PhanTypeArraySuspiciousNullable';
    const TypeSuspiciousIndirectVariable = 'PhanTypeSuspiciousIndirectVariable';
    const TypeObjectUnsetDeclaredProperty  = 'PhanTypeObjectUnsetDeclaredProperty';
    const TypeComparisonFromArray   = 'PhanTypeComparisonFromArray';
    const TypeComparisonToArray     = 'PhanTypeComparisonToArray';
    const TypeConversionFromArray   = 'PhanTypeConversionFromArray';
    const TypeInstantiateAbstract   = 'PhanTypeInstantiateAbstract';
    const TypeInstantiateInterface  = 'PhanTypeInstantiateInterface';
    const TypeInvalidCloneNotObject = 'PhanTypeInvalidCloneNotObject';
    const TypeInvalidClosureScope   = 'PhanTypeInvalidClosureScope';
    const TypeInvalidLeftOperand    = 'PhanTypeInvalidLeftOperand';
    const TypeInvalidRightOperand   = 'PhanTypeInvalidRightOperand';
    const TypeInvalidLeftOperandOfAdd  = 'PhanTypeInvalidLeftOperandOfAdd';
    const TypeInvalidRightOperandOfAdd = 'PhanTypeInvalidRightOperandOfAdd';
    const TypeInvalidLeftOperandOfNumericOp = 'PhanTypeInvalidLeftOperandOfNumericOp';
    const TypeInvalidRightOperandOfNumericOp = 'PhanTypeInvalidRightOperandOfNumericOp';
    const TypeInvalidUnaryOperandNumeric = 'PhanTypeInvalidUnaryOperandNumeric';
    const TypeInvalidUnaryOperandBitwiseNot = 'PhanTypeInvalidUnaryOperandBitwiseNot';
    const TypeInvalidUnaryOperandIncOrDec = 'PhanTypeInvalidUnaryOperandIncOrDec';
    const TypeInvalidInstanceof     = 'PhanTypeInvalidInstanceof';
    const TypeInvalidDimOffset      = 'PhanTypeInvalidDimOffset';
    const TypeInvalidDimOffsetArrayDestructuring = 'PhanTypeInvalidDimOffsetArrayDestructuring';
    const TypeInvalidExpressionArrayDestructuring = 'PhanTypeInvalidExpressionArrayDestructuring';
    const TypeInvalidThrowsNonObject             = 'PhanTypeInvalidThrowsNonObject';
    const TypeInvalidThrowsNonThrowable          = 'PhanTypeInvalidThrowsNonThrowable';
    const TypeInvalidThrowsIsTrait               = 'PhanTypeInvalidThrowsIsTrait';
    const TypeInvalidThrowsIsInterface           = 'PhanTypeInvalidThrowsIsInterface';
    const TypeMagicVoidWithReturn                = 'PhanTypeMagicVoidWithReturn';
    const TypeMismatchArgument                   = 'PhanTypeMismatchArgument';
    const TypeMismatchArgumentInternal           = 'PhanTypeMismatchArgumentInternal';
    const PartialTypeMismatchArgument            = 'PhanPartialTypeMismatchArgument';
    const PartialTypeMismatchArgumentInternal    = 'PhanPartialTypeMismatchArgumentInternal';
    const PossiblyNullTypeArgument  = 'PhanPossiblyNullTypeArgument';
    const PossiblyNullTypeArgumentInternal = 'PhanPossiblyNullTypeArgumentInternal';
    const PossiblyFalseTypeArgument  = 'PhanPossiblyFalseTypeArgument';
    const PossiblyFalseTypeArgumentInternal = 'PhanPossiblyFalseTypeArgumentInternal';

    const TypeMismatchDefault       = 'PhanTypeMismatchDefault';
    const TypeMismatchDimAssignment = 'PhanTypeMismatchDimAssignment';
    const TypeMismatchDimEmpty      = 'PhanTypeMismatchDimEmpty';
    const TypeMismatchDimFetch      = 'PhanTypeMismatchDimFetch';
    const TypeMismatchDimFetchNullable = 'PhanTypeMismatchDimFetchNullable';
    const TypeMismatchUnpackKey     = 'PhanTypeMismatchUnpackKey';
    const TypeMismatchUnpackValue   = 'PhanTypeMismatchUnpackValue';
    const TypeMismatchArrayDestructuringKey = 'PhanTypeMismatchArrayDestructuringKey';
    const TypeMismatchVariadicComment = 'PhanMismatchVariadicComment';
    const TypeMismatchVariadicParam = 'PhanMismatchVariadicParam';
    const TypeMismatchForeach       = 'PhanTypeMismatchForeach';
    const TypeNoAccessiblePropertiesForeach = 'PhanTypeNoAccessiblePropertiesForeach';
    const TypeNoPropertiesForeach = 'PhanTypeNoPropertiesForeach';
    const TypeSuspiciousNonTraversableForeach = 'PhanTypeSuspiciousNonTraversableForeach';
    const TypeMismatchProperty      = 'PhanTypeMismatchProperty';
    const PossiblyNullTypeMismatchProperty = 'PhanPossiblyNullTypeMismatchProperty';
    const PossiblyFalseTypeMismatchProperty = 'PhanPossiblyFalseTypeMismatchProperty';
    const PartialTypeMismatchProperty = 'PhanPartialTypeMismatchProperty';
    const TypeMismatchReturn        = 'PhanTypeMismatchReturn';
    const PartialTypeMismatchReturn = 'PhanPartialTypeMismatchReturn';
    const PossiblyNullTypeReturn  = 'PhanPossiblyNullTypeReturn';
    const PossiblyFalseTypeReturn  = 'PhanPossiblyFalseTypeReturn';
    const TypeMismatchDeclaredReturn = 'PhanTypeMismatchDeclaredReturn';
    const TypeMismatchDeclaredReturnNullable = 'PhanTypeMismatchDeclaredReturnNullable';
    const TypeMismatchDeclaredParam = 'PhanTypeMismatchDeclaredParam';
    const TypeMismatchDeclaredParamNullable = 'PhanTypeMismatchDeclaredParamNullable';
    const TypeMissingReturn         = 'PhanTypeMissingReturn';
    const TypeNonVarPassByRef       = 'PhanTypeNonVarPassByRef';
    const TypeParentConstructorCalled = 'PhanTypeParentConstructorCalled';
    const TypeSuspiciousEcho        = 'PhanTypeSuspiciousEcho';
    const TypeSuspiciousStringExpression = 'PhanTypeSuspiciousStringExpression';
    const TypeVoidAssignment        = 'PhanTypeVoidAssignment';
    const TypePossiblyInvalidCallable = 'PhanTypePossiblyInvalidCallable';
    const TypeInvalidCallable = 'PhanTypeInvalidCallable';
    const TypeInvalidCallableArraySize = 'PhanTypeInvalidCallableArraySize';
    const TypeInvalidCallableArrayKey = 'PhanTypeInvalidCallableArrayKey';
    const TypeInvalidCallableObjectOfMethod = 'PhanTypeInvalidCallableObjectOfMethod';
    const TypeExpectedObject        = 'PhanTypeExpectedObject';
    const TypeExpectedObjectOrClassName = 'PhanTypeExpectedObjectOrClassName';
    const TypeExpectedObjectPropAccess = 'PhanTypeExpectedObjectPropAccess';
    const TypeExpectedObjectPropAccessButGotNull = 'PhanTypeExpectedObjectPropAccessButGotNull';
    const TypeExpectedObjectStaticPropAccess = 'PhanTypeExpectedObjectStaticPropAccess';

    const TypeMismatchGeneratorYieldValue = 'PhanTypeMismatchGeneratorYieldValue';
    const TypeMismatchGeneratorYieldKey   = 'PhanTypeMismatchGeneratorYieldKey';
    const TypeInvalidYieldFrom            = 'PhanTypeInvalidYieldFrom';
    const TypeInvalidMethodName           = 'PhanTypeInvalidMethodName';
    const TypeInvalidStaticMethodName     = 'PhanTypeInvalidStaticMethodName';
    const TypeInvalidCallableMethodName   = 'PhanTypeInvalidCallableMethodName';
    const TypeInvalidRequire              = 'PhanTypeInvalidRequire';
    const TypeInvalidEval                 = 'PhanTypeInvalidEval';
    const RelativePathUsed                = 'PhanRelativePathUsed';
    const TypeInvalidTraitReturn          = 'PhanTypeInvalidTraitReturn';
    const TypeInvalidTraitParam           = 'PhanTypeInvalidTraitParam';
    const InfiniteRecursion               = 'PhanInfiniteRecursion';
    const TypeComparisonToInvalidClass    = 'PhanTypeComparisonToInvalidClass';
    const TypeComparisonToInvalidClassType = 'PhanTypeComparisonToInvalidClassType';

    // Issue::CATEGORY_ANALYSIS
    const Unanalyzable              = 'PhanUnanalyzable';
    const UnanalyzableInheritance   = 'PhanUnanalyzableInheritance';
    const InvalidConstantFQSEN      = 'PhanInvalidConstantFQSEN';

    // Issue::CATEGORY_VARIABLE
    const VariableUseClause         = 'PhanVariableUseClause';

    // Issue::CATEGORY_STATIC
    const StaticCallToNonStatic     = 'PhanStaticCallToNonStatic';
    const StaticPropIsStaticType    = 'PhanStaticPropIsStaticType';

    // Issue::CATEGORY_CONTEXT
    const ContextNotObject          = 'PhanContextNotObject';
    const ContextNotObjectInCallable = 'PhanContextNotObjectInCallable';
    const ContextNotObjectUsingSelf = 'PhanContextNotObjectUsingSelf';

    // Issue::CATEGORY_DEPRECATED
    const DeprecatedClass           = 'PhanDeprecatedClass';
    const DeprecatedInterface       = 'PhanDeprecatedInterface';
    const DeprecatedTrait           = 'PhanDeprecatedTrait';
    const DeprecatedFunction        = 'PhanDeprecatedFunction';
    const DeprecatedFunctionInternal = 'PhanDeprecatedFunctionInternal';
    const DeprecatedProperty        = 'PhanDeprecatedProperty';
    const DeprecatedCaseInsensitiveDefine = 'PhanDeprecatedCaseInsensitiveDefine';

    // Issue::CATEGORY_PARAMETER
    const ParamReqAfterOpt          = 'PhanParamReqAfterOpt';
    const ParamSpecial1             = 'PhanParamSpecial1';
    const ParamSpecial2             = 'PhanParamSpecial2';
    const ParamSpecial3             = 'PhanParamSpecial3';
    const ParamSpecial4             = 'PhanParamSpecial4';
    const ParamSuspiciousOrder      = 'PhanParamSuspiciousOrder';
    const ParamTooFew               = 'PhanParamTooFew';
    const ParamTooFewInternal       = 'PhanParamTooFewInternal';
    const ParamTooFewCallable       = 'PhanParamTooFewCallable';
    const ParamTooMany              = 'PhanParamTooMany';
    const ParamTooManyUnpack        = 'PhanParamTooManyUnpack';
    const ParamTooManyInternal      = 'PhanParamTooManyInternal';
    const ParamTooManyUnpackInternal = 'PhanParamTooManyUnpackInternal';
    const ParamTooManyCallable      = 'PhanParamTooManyCallable';
    const ParamTypeMismatch         = 'PhanParamTypeMismatch';
    const ParamSignatureMismatch    = 'PhanParamSignatureMismatch';
    const ParamSignatureMismatchInternal = 'PhanParamSignatureMismatchInternal';
    const ParamRedefined            = 'PhanParamRedefined';

    const ParamSignatureRealMismatchReturnType                        = 'PhanParamSignatureRealMismatchReturnType';
    const ParamSignatureRealMismatchReturnTypeInternal                = 'PhanParamSignatureRealMismatchReturnTypeInternal';
    const ParamSignaturePHPDocMismatchReturnType                      = 'PhanParamSignaturePHPDocMismatchReturnType';
    const ParamSignatureRealMismatchTooManyRequiredParameters         = 'PhanParamSignatureRealMismatchTooManyRequiredParameters';
    const ParamSignatureRealMismatchTooManyRequiredParametersInternal = 'PhanParamSignatureRealMismatchTooManyRequiredParametersInternal';
    const ParamSignaturePHPDocMismatchTooManyRequiredParameters       = 'PhanParamSignaturePHPDocMismatchTooManyRequiredParameters';
    const ParamSignatureRealMismatchTooFewParameters                  = 'PhanParamSignatureRealMismatchTooFewParameters';
    const ParamSignatureRealMismatchTooFewParametersInternal          = 'PhanParamSignatureRealMismatchTooFewParametersInternal';
    const ParamSignaturePHPDocMismatchTooFewParameters                = 'PhanParamSignaturePHPDocMismatchTooFewParameters';
    const ParamSignatureRealMismatchHasParamType                      = 'PhanParamSignatureRealMismatchHasParamType';
    const ParamSignatureRealMismatchHasParamTypeInternal              = 'PhanParamSignatureRealMismatchHasParamTypeInternal';
    const ParamSignaturePHPDocMismatchHasParamType                    = 'PhanParamSignaturePHPDocMismatchHasParamType';
    const ParamSignatureRealMismatchHasNoParamType                    = 'PhanParamSignatureRealMismatchHasNoParamType';
    const ParamSignatureRealMismatchHasNoParamTypeInternal            = 'PhanParamSignatureRealMismatchHasNoParamTypeInternal';
    const ParamSignaturePHPDocMismatchHasNoParamType                  = 'PhanParamSignaturePHPDocMismatchHasNoParamType';
    const ParamSignatureRealMismatchParamIsReference                  = 'PhanParamSignatureRealMismatchParamIsReference';
    const ParamSignatureRealMismatchParamIsReferenceInternal          = 'PhanParamSignatureRealMismatchParamIsReferenceInternal';
    const ParamSignaturePHPDocMismatchParamIsReference                = 'PhanParamSignaturePHPDocMismatchParamIsReference';
    const ParamSignatureRealMismatchParamIsNotReference               = 'PhanParamSignatureRealMismatchParamIsNotReference';
    const ParamSignatureRealMismatchParamIsNotReferenceInternal       = 'PhanParamSignatureRealMismatchParamIsNotReferenceInternal';
    const ParamSignaturePHPDocMismatchParamIsNotReference             = 'PhanParamSignaturePHPDocMismatchParamIsNotReference';
    const ParamSignatureRealMismatchParamVariadic                     = 'PhanParamSignatureRealMismatchParamVariadic';
    const ParamSignatureRealMismatchParamVariadicInternal             = 'PhanParamSignatureRealMismatchParamVariadicInternal';
    const ParamSignaturePHPDocMismatchParamVariadic                   = 'PhanParamSignaturePHPDocMismatchParamVariadic';
    const ParamSignatureRealMismatchParamNotVariadic                  = 'PhanParamSignatureRealMismatchParamNotVariadic';
    const ParamSignatureRealMismatchParamNotVariadicInternal          = 'PhanParamSignatureRealMismatchParamNotVariadicInternal';
    const ParamSignaturePHPDocMismatchParamNotVariadic                = 'PhanParamSignaturePHPDocMismatchParamNotVariadic';
    const ParamSignatureRealMismatchParamType                         = 'PhanParamSignatureRealMismatchParamType';
    const ParamSignatureRealMismatchParamTypeInternal                 = 'PhanParamSignatureRealMismatchParamTypeInternal';
    const ParamSignaturePHPDocMismatchParamType                       = 'PhanParamSignaturePHPDocMismatchParamType';

    // Issue::CATEGORY_NOOP
    const NoopArray                     = 'PhanNoopArray';
    const NoopClosure                   = 'PhanNoopClosure';
    const NoopConstant                  = 'PhanNoopConstant';
    const NoopProperty                  = 'PhanNoopProperty';
    const NoopVariable                  = 'PhanNoopVariable';
    const NoopUnaryOperator             = 'PhanNoopUnaryOperator';
    const NoopBinaryOperator            = 'PhanNoopBinaryOperator';
    const NoopStringLiteral             = 'PhanNoopStringLiteral';
    const NoopEncapsulatedStringLiteral = 'PhanNoopEncapsulatedStringLiteral';
    const NoopNumericLiteral            = 'PhanNoopNumericLiteral';
    const UnreachableCatch              = 'PhanUnreachableCatch';
    const UnreferencedClass             = 'PhanUnreferencedClass';
    const UnreferencedFunction          = 'PhanUnreferencedFunction';
    const UnreferencedPublicMethod      = 'PhanUnreferencedPublicMethod';
    const UnreferencedProtectedMethod   = 'PhanUnreferencedProtectedMethod';
    const UnreferencedPrivateMethod     = 'PhanUnreferencedPrivateMethod';
    const UnreferencedPublicProperty    = 'PhanUnreferencedPublicProperty';
    const UnreferencedProtectedProperty = 'PhanUnreferencedProtectedProperty';
    const UnreferencedPrivateProperty   = 'PhanUnreferencedPrivateProperty';
    const ReadOnlyPublicProperty        = 'PhanReadOnlyPublicProperty';
    const ReadOnlyProtectedProperty     = 'PhanReadOnlyProtectedProperty';
    const ReadOnlyPrivateProperty       = 'PhanReadOnlyPrivateProperty';
    const WriteOnlyPublicProperty       = 'PhanWriteOnlyPublicProperty';
    const WriteOnlyProtectedProperty    = 'PhanWriteOnlyProtectedProperty';
    const WriteOnlyPrivateProperty      = 'PhanWriteOnlyPrivateProperty';
    const UnreferencedConstant          = 'PhanUnreferencedConstant';
    const UnreferencedPublicClassConstant = 'PhanUnreferencedPublicClassConstant';
    const UnreferencedProtectedClassConstant = 'PhanUnreferencedProtectedClassConstant';
    const UnreferencedPrivateClassConstant = 'PhanUnreferencedPrivateClassConstant';
    const UnreferencedClosure           = 'PhanUnreferencedClosure';
    const UnreferencedUseNormal         = 'PhanUnreferencedUseNormal';
    const UnreferencedUseFunction       = 'PhanUnreferencedUseFunction';
    const UnreferencedUseConstant       = 'PhanUnreferencedUseConstant';
    const UseNormalNoEffect             = 'PhanUseNormalNoEffect';
    const UseNormalNamespacedNoEffect   = 'PhanUseNormalNamespacedNoEffect';
    const UseFunctionNoEffect           = 'PhanUseFunctionNoEffect';
    const UseContantNoEffect           = 'PhanUseContantNoEffect';

    const UnusedVariable                        = 'PhanUnusedVariable';
    const UnusedPublicMethodParameter           = 'PhanUnusedPublicMethodParameter';
    const UnusedPublicFinalMethodParameter      = 'PhanUnusedPublicFinalMethodParameter';
    const UnusedProtectedMethodParameter        = 'PhanUnusedProtectedMethodParameter';
    const UnusedProtectedFinalMethodParameter   = 'PhanUnusedProtectedFinalMethodParameter';
    const UnusedPrivateMethodParameter          = 'PhanUnusedPrivateMethodParameter';
    const UnusedPrivateFinalMethodParameter     = 'PhanUnusedPrivateFinalMethodParameter';
    const UnusedClosureUseVariable              = 'PhanUnusedClosureUseVariable';
    const UnusedClosureParameter                = 'PhanUnusedClosureParameter';
    const UnusedGlobalFunctionParameter         = 'PhanUnusedGlobalFunctionParameter';
    const UnusedVariableValueOfForeachWithKey   = 'PhanUnusedVariableValueOfForeachWithKey';  // has higher false positive rates than UnusedVariable
    const UnusedVariableCaughtException         = 'PhanUnusedVariableCaughtException';  // has higher false positive rates than UnusedVariable

    // Issue::CATEGORY_REDEFINE
    const RedefineClass             = 'PhanRedefineClass';
    const RedefineClassAlias        = 'PhanRedefineClassAlias';
    const RedefineClassInternal     = 'PhanRedefineClassInternal';
    const RedefineFunction          = 'PhanRedefineFunction';
    const RedefineFunctionInternal  = 'PhanRedefineFunctionInternal';
    const IncompatibleCompositionProp = 'PhanIncompatibleCompositionProp';
    const IncompatibleCompositionMethod = 'PhanIncompatibleCompositionMethod';
    const RedefinedUsedTrait            = 'PhanRedefinedUsedTrait';
    const RedefinedInheritedInterface   = 'PhanRedefinedInheritedInterface';
    const RedefinedExtendedClass        = 'PhanRedefinedExtendedClass';

    // Issue::CATEGORY_ACCESS
    const AccessPropertyPrivate     = 'PhanAccessPropertyPrivate';
    const AccessPropertyProtected   = 'PhanAccessPropertyProtected';

    const AccessReadOnlyProperty       = 'PhanAccessReadOnlyProperty';
    const AccessWriteOnlyProperty      = 'PhanAccessWriteOnlyProperty';
    const AccessReadOnlyMagicProperty  = 'PhanAccessReadOnlyMagicProperty';
    const AccessWriteOnlyMagicProperty = 'PhanAccessWriteOnlyMagicProperty';

    const AccessMethodPrivate       = 'PhanAccessMethodPrivate';
    const AccessMethodPrivateWithCallMagicMethod = 'PhanAccessMethodPrivateWithCallMagicMethod';
    const AccessMethodProtected     = 'PhanAccessMethodProtected';
    const AccessMethodProtectedWithCallMagicMethod = 'PhanAccessMethodProtectedWithCallMagicMethod';
    const AccessSignatureMismatch         = 'PhanAccessSignatureMismatch';
    const AccessSignatureMismatchInternal = 'PhanAccessSignatureMismatchInternal';
    const PropertyAccessSignatureMismatch = 'PhanPropertyAccessSignatureMismatch';
    const PropertyAccessSignatureMismatchInternal  = 'PhanPropertyAccessSignatureMismatchInternal';
    const AccessConstantSignatureMismatch = 'PhanConstantAccessSignatureMismatch';
    const AccessConstantSignatureMismatchInternal  = 'PhanConstantAccessSignatureMismatchInternal';
    const AccessStaticToNonStatic         = 'PhanAccessStaticToNonStatic';
    const AccessNonStaticToStatic         = 'PhanAccessNonStaticToStatic';
    const AccessStaticToNonStaticProperty = 'PhanAccessStaticToNonStaticProperty';
    const AccessNonStaticToStaticProperty = 'PhanAccessNonStaticToStaticProperty';
    const AccessClassConstantPrivate      = 'PhanAccessClassConstantPrivate';
    const AccessClassConstantProtected    = 'PhanAccessClassConstantProtected';
    const AccessPropertyStaticAsNonStatic = 'PhanAccessPropertyStaticAsNonStatic';
    const AccessPropertyNonStaticAsStatic = 'PhanAccessPropertyNonStaticAsStatic';
    const AccessOwnConstructor            = 'PhanAccessOwnConstructor';

    const AccessConstantInternal    = 'PhanAccessConstantInternal';
    const AccessClassInternal       = 'PhanAccessClassInternal';
    const AccessClassConstantInternal = 'PhanAccessClassConstantInternal';
    const AccessPropertyInternal    = 'PhanAccessPropertyInternal';
    const AccessMethodInternal      = 'PhanAccessMethodInternal';
    const AccessWrongInheritanceCategory = 'PhanAccessWrongInheritanceCategory';
    const AccessWrongInheritanceCategoryInternal = 'PhanAccessWrongInheritanceCategoryInternal';
    const AccessExtendsFinalClass                = 'PhanAccessExtendsFinalClass';
    const AccessExtendsFinalClassInternal        = 'PhanAccessExtendsFinalClassInternal';
    const AccessOverridesFinalMethod             = 'PhanAccessOverridesFinalMethod';
    const AccessOverridesFinalMethodInternal     = 'PhanAccessOverridesFinalMethodInternal';
    const AccessOverridesFinalMethodPHPDoc       = 'PhanAccessOverridesFinalMethodPHPDoc';

    // Issue::CATEGORY_COMPATIBLE
    const CompatibleExpressionPHP7           = 'PhanCompatibleExpressionPHP7';
    const CompatiblePHP7                     = 'PhanCompatiblePHP7';
    const CompatibleNullableTypePHP70        = 'PhanCompatibleNullableTypePHP70';
    const CompatibleShortArrayAssignPHP70    = 'PhanCompatibleShortArrayAssignPHP70';
    const CompatibleKeyedArrayAssignPHP70    = 'PhanCompatibleKeyedArrayAssignPHP70';
    const CompatibleVoidTypePHP70            = 'PhanCompatibleVoidTypePHP70';
    const CompatibleIterableTypePHP70        = 'PhanCompatibleIterableTypePHP70';
    const CompatibleObjectTypePHP71          = 'PhanCompatibleNullableTypePHP71';
    const CompatibleUseVoidPHP70             = 'PhanCompatibleUseVoidPHP70';
    const CompatibleUseIterablePHP71         = 'PhanCompatibleUseIterablePHP71';
    const CompatibleUseObjectPHP71           = 'PhanCompatibleUseObjectPHP71';
    const CompatibleMultiExceptionCatchPHP70 = 'PhanCompatibleMultiExceptionCatchPHP70';
    const CompatibleNegativeStringOffset     = 'PhanCompatibleNegativeStringOffset';

    // Issue::CATEGORY_GENERIC
    const TemplateTypeConstant       = 'PhanTemplateTypeConstant';
    const TemplateTypeStaticMethod   = 'PhanTemplateTypeStaticMethod';
    const TemplateTypeStaticProperty = 'PhanTemplateTypeStaticProperty';
    const GenericGlobalVariable      = 'PhanGenericGlobalVariable';
    const GenericConstructorTypes    = 'PhanGenericConstructorTypes';
    const TemplateTypeNotUsedInFunctionReturn = 'PhanTemplateTypeNotUsedInFunctionReturn';
    const TemplateTypeNotDeclaredInFunctionParams = 'PhanTemplateTypeNotDeclaredInFunctionParams';

    // Issue::CATEGORY_COMMENT
    const InvalidCommentForDeclarationType = 'PhanInvalidCommentForDeclarationType';
    const MisspelledAnnotation             = 'PhanMisspelledAnnotation';
    const UnextractableAnnotation          = 'PhanUnextractableAnnotation';
    const UnextractableAnnotationPart      = 'PhanUnextractableAnnotationPart';
    const UnextractableAnnotationSuffix    = 'PhanUnextractableAnnotationSuffix';
    const UnextractableAnnotationElementName = 'PhanUnextractableAnnotationElementName';
    const CommentParamWithoutRealParam     = 'PhanCommentParamWithoutRealParam';
    const CommentParamAssertionWithoutRealParam = 'PhanCommentParamAssertionWithoutRealParam';
    const CommentParamOnEmptyParamList     = 'PhanCommentParamOnEmptyParamList';
    const CommentOverrideOnNonOverrideMethod = 'PhanCommentOverrideOnNonOverrideMethod';
    const CommentOverrideOnNonOverrideConstant = 'PhanCommentOverrideOnNonOverrideConstant';
    const CommentParamOutOfOrder           = 'PhanCommentParamOutOfOrder';
    const ThrowTypeAbsent                  = 'PhanThrowTypeAbsent';
    const ThrowTypeAbsentForCall           = 'PhanThrowTypeAbsentForCall';
    const ThrowTypeMismatch                = 'PhanThrowTypeMismatch';
    const ThrowTypeMismatchForCall         = 'PhanThrowTypeMismatchForCall';
    const CommentAmbiguousClosure          = 'PhanCommentAmbiguousClosure';
    const CommentDuplicateParam            = 'PhanCommentDuplicateParam';
    const CommentDuplicateMagicMethod      = 'PhanCommentDuplicateMagicMethod';
    const CommentDuplicateMagicProperty    = 'PhanCommentDuplicateMagicProperty';
    // phpcs:enable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
    // end of issue name constants

    /** This category of issue is emitted when you're trying to access things that you can't access. */
    const CATEGORY_ACCESS            = 1 << 1;
    /** This category will be emitted when Phan doesn't know how to analyze something. */
    const CATEGORY_ANALYSIS          = 1 << 2;
    /** This category of issue is emitted when there are compatibility issues between PHP versions */
    const CATEGORY_COMPATIBLE        = 1 << 3;
    /** This category of issue is for when you're doing stuff out of the context in which you're allowed to do it, e.g. referencing `self` or `parent` when not in a class, interface or trait. */
    const CATEGORY_CONTEXT           = 1 << 4;
    /** This category of issue comes up when you're accessing deprecated elements (as marked by the `@deprecated` comment). */
    const CATEGORY_DEPRECATED        = 1 << 5;
    /** Issues in this category are emitted when you have reasonable code but it isn't doing anything. */
    const CATEGORY_NOOP              = 1 << 6;
    /** This category of error comes up when you're messing up your method or function parameters in some way. */
    const CATEGORY_PARAMETER         = 1 << 7;
    /** This category of issue comes up when more than one thing of whatever type have the same name and namespace. */
    const CATEGORY_REDEFINE          = 1 << 8;
    /** Static access to non-static methods, etc. */
    const CATEGORY_STATIC            = 1 << 9;
    /** This category of issue come from using incorrect types or types that cannot cast to the expected types. */
    const CATEGORY_TYPE              = 1 << 10;
    /** This category of issue comes up when there are references to undefined things. */
    const CATEGORY_UNDEFINED         = 1 << 11;
    /** This category is for using non-variables where variables are expected. */
    const CATEGORY_VARIABLE          = 1 << 12;
    /** This category is for plugins. */
    const CATEGORY_PLUGIN            = 1 << 13;
    /** This category contains issues related to [Phan's generic type support](https://github.com/phan/phan/wiki/Generic-Types). */
    const CATEGORY_GENERIC           = 1 << 14;
    /** This issue category comes up when there is an attempt to access an `(at)internal` element outside of the namespace in which it's defined. */
    const CATEGORY_INTERNAL          = 1 << 15;
    /** This is emitted for some (but not all) comments which Phan thinks are invalid or unparsable. */
    const CATEGORY_COMMENT           = 1 << 16;
    /** Emitted for syntax errors. */
    const CATEGORY_SYNTAX            = 1 << 17;

    const CATEGORY_NAME = [
        self::CATEGORY_ACCESS            => 'AccessError',
        self::CATEGORY_ANALYSIS          => 'Analysis',
        self::CATEGORY_COMMENT           => 'CommentError',
        self::CATEGORY_COMPATIBLE        => 'CompatError',
        self::CATEGORY_CONTEXT           => 'Context',
        self::CATEGORY_DEPRECATED        => 'DeprecatedError',
        self::CATEGORY_GENERIC           => 'Generic',
        self::CATEGORY_INTERNAL          => 'Internal',
        self::CATEGORY_NOOP              => 'NOOPError',
        self::CATEGORY_PARAMETER         => 'ParamError',
        self::CATEGORY_PLUGIN            => 'Plugin',
        self::CATEGORY_REDEFINE          => 'RedefineError',
        self::CATEGORY_STATIC            => 'StaticCallError',
        self::CATEGORY_SYNTAX            => 'Syntax',
        self::CATEGORY_TYPE              => 'TypeError',
        self::CATEGORY_UNDEFINED         => 'UndefError',
        self::CATEGORY_VARIABLE          => 'VarError',
    ];

    /** Low severity. E.g. documentation errors or code that would cause a (typically harmless) PHP notice. */
    const SEVERITY_LOW      = 0;
    /** Normal severity. E.g. something that may cause a minor bug. */
    const SEVERITY_NORMAL   = 5;
    /** Highest severity. Likely to cause an uncaught Error, Exception, or fatal error at runtime. */
    const SEVERITY_CRITICAL = 10;

    // See https://docs.codeclimate.com/v1.0/docs/remediation
    // TODO: Decide on a way to estimate these and bring these up to date once codeclimate updates phan.
    // Right now, almost everything is REMEDIATION_B.
    const REMEDIATION_A = 1000000;
    const REMEDIATION_B = 3000000;
    /** @suppress PhanUnreferencedPublicClassConstant */
    const REMEDIATION_C = 6000000;
    /** @suppress PhanUnreferencedPublicClassConstant */
    const REMEDIATION_D = 12000000;
    /** @suppress PhanUnreferencedPublicClassConstant */
    const REMEDIATION_E = 16000000;
    /** @suppress PhanUnreferencedPublicClassConstant */
    const REMEDIATION_F = 18000000;

    // type id constants.
    const TYPE_ID_UNKNOWN = 999;

    // Keep sorted and in sync with Colorizing::default_color_for_template
    const UNCOLORED_FORMAT_STRING_FOR_TEMPLATE = [
        'CLASS'         => '%s',
        'CLASSLIKE'     => '%s',
        'CODE'          => '%s',  // A snippet from the code
        'COMMENT'       => '%s',  // contents of a phpdoc comment
        'CONST'         => '%s',
        'COUNT'         => '%d',
        'DETAILS'       => '%s',  // additional details about an error
        'FILE'          => '%s',
        'FUNCTIONLIKE'  => '%s',
        'FUNCTION'      => '%s',
        'INDEX'         => '%d',
        'INTERFACE'     => '%s',
        'ISSUETYPE'     => '%s',  // used by Phan\Output\Printer, for minor issues.
        'ISSUETYPE_CRITICAL' => '%s',  // for critical issues
        'ISSUETYPE_NORMAL' => '%s',  // for normal issues
        'LINE'          => '%d',
        'METHOD'        => '%s',
        'NAMESPACE'     => '%s',
        'OPERATOR'      => '%s',
        'PARAMETER'     => '%s',
        'PROPERTY'      => '%s',
        'SCALAR'        => '%s',  // A scalar from the code
        'STRING_LITERAL' => '%s',  // A string literal from the code
        'SUGGESTION'    => '%s',
        'TYPE'          => '%s',
        'TRAIT'         => '%s',
        'VARIABLE'      => '%s',
    ];

    /** @var string the type of this issue */
    private $type;

    /**
     * @var int (a preferably unique integer for $type, for the pylint output formatter)
     * Built in issue types must have a unique type id.
     */
    private $type_id;

    /** @var int the category of this issue (self::CATEGORY_*) */
    private $category;

    /** @var int the severity of this issue (self::SEVERITY_*) */
    private $severity;

    /** @var string The format string for this issue type. Contains a mix of {CLASS} and %s/%d annotations. Used for colorizing option. */
    private $template_raw;

    /** @var string The printf format string for this issue type. If --color is enabled, this will have unix color codes. */
    private $template;

    /** @var int self::REMEDIATION_* */
    private $remediation_difficulty;

    /**
     * @param string $type the type of this issue
     * @param int $category the category of this issue (self::CATEGORY_*)
     * @param int $severity the severity of this issue (self::SEVERITY_*)
     * @param string $template_raw the template string for issue messages. Contains a mix of {CLASS} and %s/%d annotations.
     * @param int $remediation_difficulty self::REMEDIATION_*
     * @param int $type_id (unique integer id for $type)
     */
    public function __construct(
        string $type,
        int $category,
        int $severity,
        string $template_raw,
        int $remediation_difficulty,
        int $type_id
    ) {
        $this->type = $type;
        $this->category = $category;
        $this->severity = $severity;
        $this->template_raw = $template_raw;
        $this->template = self::templateToFormatString($template_raw);
        $this->remediation_difficulty = $remediation_difficulty;
        $this->type_id = $type_id;
    }

    private static function templateToFormatString(
        string $template
    ) : string {
        /** @param array<int,string> $matches */
        return preg_replace_callback('/{([A-Z_]+)}/', function (array $matches) use ($template): string {
            $key = $matches[1];
            $replacement_exists = \array_key_exists($key, self::UNCOLORED_FORMAT_STRING_FOR_TEMPLATE);
            if (!$replacement_exists) {
                error_log(sprintf(
                    "No coloring info for issue message (%s), key {%s}. Valid template types: %s",
                    $template,
                    $key,
                    implode(', ', array_keys(self::UNCOLORED_FORMAT_STRING_FOR_TEMPLATE))
                ));
                return '%s';
            }
            return self::UNCOLORED_FORMAT_STRING_FOR_TEMPLATE[$key];
        }, $template);
    }

    /**
     * @return array<string,Issue>
     */
    public static function issueMap()
    {
        static $error_map;

        if (\is_array($error_map)) {
            return $error_map;
        }

        // phpcs:disable Generic.Files.LineLength
        /**
         * @var array<int,Issue>
         * Note: All type ids should be unique, and be grouped by the category.
         * (E.g. If the category is (1 << x), then the type_id should be x*1000 + y
         * If new type ids are added, existing ones should not be changed.
         */
        $error_list = [
            // Issue::CATEGORY_SYNTAX
            new Issue(
                self::SyntaxError,
                self::CATEGORY_SYNTAX,
                self::SEVERITY_CRITICAL,
                "%s",
                self::REMEDIATION_A,
                17000
            ),
            new Issue(
                self::InvalidConstantExpression,
                self::CATEGORY_SYNTAX,
                self::SEVERITY_CRITICAL,
                "Constant expression contains invalid operations",
                self::REMEDIATION_A,
                17001
            ),
            new Issue(
                self::InvalidNode,
                self::CATEGORY_SYNTAX,
                self::SEVERITY_CRITICAL,
                "%s",
                self::REMEDIATION_A,
                17002
            ),
            new Issue(
                self::InvalidWriteToTemporaryExpression,
                self::CATEGORY_SYNTAX,
                self::SEVERITY_CRITICAL,
                "Cannot use temporary expression (of type {TYPE}) in write context",
                self::REMEDIATION_A,
                17003
            ),
            new Issue(
                self::InvalidTraitUse,
                self::CATEGORY_SYNTAX,
                self::SEVERITY_CRITICAL,
                'Invalid trait use: {DETAILS}',
                self::REMEDIATION_A,
                17004
            ),
            // Could try to make a better suggestion, optionally
            new Issue(
                self::ContinueTargetingSwitch,
                self::CATEGORY_SYNTAX,
                self::SEVERITY_NORMAL,
                '"continue" targeting switch is equivalent to "break". Did you mean to use "continue 2"?',
                self::REMEDIATION_A,
                17005
            ),
            new Issue(
                self::ContinueOrBreakNotInLoop,
                self::CATEGORY_SYNTAX,
                self::SEVERITY_CRITICAL,
                '\'{OPERATOR}\' not in the \'loop\' or \'switch\' context.',
                self::REMEDIATION_A,
                17006
            ),
            new Issue(
                self::ContinueOrBreakTooManyLevels,
                self::CATEGORY_SYNTAX,
                self::SEVERITY_CRITICAL,
                'Cannot \'{OPERATOR}\' {INDEX} levels.',
                self::REMEDIATION_A,
                17007
            ),

            // Issue::CATEGORY_UNDEFINED
            new Issue(
                self::EmptyFile,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_LOW,
                "Empty file {FILE}",
                self::REMEDIATION_B,
                11000
            ),
            new Issue(
                self::MissingRequireFile,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Missing required file {FILE}",
                self::REMEDIATION_B,
                11040
            ),
            new Issue(
                self::InvalidRequireFile,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Required file {FILE} is not a file",
                self::REMEDIATION_B,
                11041
            ),
            new Issue(
                self::ParentlessClass,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Reference to parent of class {CLASS} that does not extend anything",
                self::REMEDIATION_B,
                11001
            ),
            new Issue(
                self::UndeclaredClass,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Reference to undeclared class {CLASS}",
                self::REMEDIATION_B,
                11002
            ),
            new Issue(
                self::UndeclaredExtendedClass,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Class extends undeclared class {CLASS}",
                self::REMEDIATION_B,
                11003
            ),
            new Issue(
                self::UndeclaredInterface,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Class implements undeclared interface {INTERFACE}",
                self::REMEDIATION_B,
                11004
            ),
            new Issue(
                self::UndeclaredTrait,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Class uses undeclared trait {TRAIT}",
                self::REMEDIATION_B,
                11005
            ),
            new Issue(
                self::UndeclaredClassCatch,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Catching undeclared class {CLASS}",
                self::REMEDIATION_B,
                11006
            ),
            new Issue(
                self::UndeclaredClassConstant,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Reference to constant {CONST} from undeclared class {CLASS}",
                self::REMEDIATION_B,
                11007
            ),
            new Issue(
                self::UndeclaredClassInstanceof,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Checking instanceof against undeclared class {CLASS}",
                self::REMEDIATION_B,
                11008
            ),
            new Issue(
                self::UndeclaredClassMethod,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Call to method {METHOD} from undeclared class {CLASS}",
                self::REMEDIATION_B,
                11009
            ),
            new Issue(
                self::UndeclaredClassProperty,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Reference to instance property {PROPERTY} from undeclared class {CLASS}",
                self::REMEDIATION_B,
                11038
            ),
            new Issue(
                self::UndeclaredClassStaticProperty,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Reference to static property {PROPERTY} from undeclared class {CLASS}",
                self::REMEDIATION_B,
                11039
            ),
            new Issue(
                self::UndeclaredClassReference,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Reference to undeclared class {CLASS}",
                self::REMEDIATION_B,
                11010
            ),
            new Issue(
                self::UndeclaredConstant,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Reference to undeclared constant {CONST}",
                self::REMEDIATION_B,
                11011
            ),
            new Issue(
                self::UndeclaredFunction,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Call to undeclared function {FUNCTION}",
                self::REMEDIATION_B,
                11012
            ),
            new Issue(
                self::UndeclaredMethod,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Call to undeclared method {METHOD}",
                self::REMEDIATION_B,
                11013
            ),
            new Issue(
                self::UndeclaredStaticMethod,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Static call to undeclared method {METHOD}",
                self::REMEDIATION_B,
                11014
            ),
            new Issue(
                self::UndeclaredProperty,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Reference to undeclared property {PROPERTY}",
                self::REMEDIATION_B,
                11015
            ),
            new Issue(
                self::UndeclaredStaticProperty,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Static property '{PROPERTY}' on {CLASS} is undeclared",
                self::REMEDIATION_B,
                11016
            ),
            new Issue(
                self::TraitParentReference,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_LOW,
                "Reference to parent from trait {TRAIT}",
                self::REMEDIATION_B,
                11017
            ),
            new Issue(
                self::UndeclaredVariable,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Variable \${VARIABLE} is undeclared",
                self::REMEDIATION_B,
                11018
            ),
            new Issue(
                self::UndeclaredTypeParameter,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Parameter \${PARAMETER} has undeclared type {TYPE}",
                self::REMEDIATION_B,
                11019
            ),
            new Issue(
                self::UndeclaredTypeProperty,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Property {PROPERTY} has undeclared type {TYPE}",
                self::REMEDIATION_B,
                11020
            ),
            new Issue(
                self::UndeclaredClosureScope,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Reference to undeclared class {CLASS} in @phan-closure-scope",
                self::REMEDIATION_B,
                11021
            ),
            new Issue(
                self::ClassContainsAbstractMethod,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "non-abstract class {CLASS} contains abstract method {METHOD} declared at {FILE}:{LINE}",
                self::REMEDIATION_B,
                11022
            ),
            new Issue(
                self::ClassContainsAbstractMethodInternal,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "non-abstract class {CLASS} contains abstract internal method {METHOD}",
                self::REMEDIATION_B,
                11023
            ),
            new Issue(
                self::UndeclaredAliasedMethodOfTrait,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Alias {METHOD} was defined for a method {METHOD} which does not exist in trait {TRAIT}",
                self::REMEDIATION_B,
                11024
            ),
            new Issue(
                self::RequiredTraitNotAdded,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Required trait {TRAIT} for trait adaptation was not added to class",
                self::REMEDIATION_B,
                11025
            ),
            new Issue(
                self::AmbiguousTraitAliasSource,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Trait alias {METHOD} has an ambiguous source method {METHOD} with more than one possible source trait. Possibilities: {TRAIT}",
                self::REMEDIATION_B,
                11026
            ),
            new Issue(
                self::UndeclaredVariableDim,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_LOW,
                "Variable \${VARIABLE} was undeclared, but array fields are being added to it.",
                self::REMEDIATION_B,
                11027
            ),
            new Issue(
                self::UndeclaredVariableAssignOp,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_LOW,
                "Variable \${VARIABLE} was undeclared, but it is being used as the left-hand side of an assignment operation",
                self::REMEDIATION_B,
                11037
            ),
            new Issue(
                self::UndeclaredTypeReturnType,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Return type of {METHOD} is undeclared type {TYPE}",
                self::REMEDIATION_B,
                11028
            ),
            new Issue(
                self::UndeclaredTypeThrowsType,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "@throws type of {METHOD} has undeclared type {TYPE}",
                self::REMEDIATION_B,
                11034
            ),
            new Issue(
                self::UndeclaredClassAliasOriginal,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_CRITICAL,
                "Reference to undeclared class {CLASS} for the original class of a class_alias for {CLASS}",
                self::REMEDIATION_B,
                11029
            ),
            new Issue(
                self::UndeclaredClassInCallable,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Reference to undeclared class {CLASS} in callable {METHOD}",
                self::REMEDIATION_B,
                11030
            ),
            new Issue(
                self::UndeclaredStaticMethodInCallable,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Reference to undeclared static method {METHOD} in callable",
                self::REMEDIATION_B,
                11031
            ),
            new Issue(
                self::UndeclaredFunctionInCallable,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Call to undeclared function {FUNCTION} in callable",
                self::REMEDIATION_B,
                11032
            ),
            new Issue(
                self::UndeclaredMethodInCallable,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Call to undeclared method {METHOD} in callable. Possible object type(s) for that method are {TYPE}",
                self::REMEDIATION_B,
                11033
            ),
            new Issue(
                self::EmptyFQSENInCallable,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Possible call to a function '{FUNCTIONLIKE}' with an empty FQSEN.",
                self::REMEDIATION_B,
                11035
            ),
            new Issue(
                self::EmptyFQSENInClasslike,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Possible use of a classlike '{CLASSLIKE}' with an empty FQSEN.",
                self::REMEDIATION_B,
                11036
            ),
            new Issue(
                self::InvalidFQSENInCallable,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Possible call to a function '{FUNCTIONLIKE}' with an invalid FQSEN.",
                self::REMEDIATION_B,
                11042
            ),
            new Issue(
                self::InvalidFQSENInClasslike,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_NORMAL,
                "Possible use of a classlike '{CLASSLIKE}' with an invalid FQSEN.",
                self::REMEDIATION_B,
                11043
            ),
            new Issue(
                self::UndeclaredMagicConstant,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_LOW,
                "Reference to magic constant {CONST} that is undeclared in the current scope",
                self::REMEDIATION_B,
                11044
            ),
            new Issue(
                self::UndeclaredInvokeInCallable,
                self::CATEGORY_UNDEFINED,
                self::SEVERITY_LOW,
                "Possible attempt to access missing magic method {FUNCTIONLIKE} of '{CLASS}'",
                self::REMEDIATION_B,
                11045
            ),

            // Issue::CATEGORY_ANALYSIS
            new Issue(
                self::Unanalyzable,
                self::CATEGORY_ANALYSIS,
                self::SEVERITY_LOW,
                "Expression is unanalyzable or feature is unimplemented. Please create an issue at https://github.com/phan/phan/issues/new.",
                self::REMEDIATION_B,
                2000
            ),
            new Issue(
                self::UnanalyzableInheritance,
                self::CATEGORY_ANALYSIS,
                self::SEVERITY_LOW,
                "Unable to determine the method(s) which {METHOD} overrides, but Phan inferred that it did override something earlier. Please create an issue at https://github.com/phan/phan/issues/new with a test case.",
                self::REMEDIATION_B,
                2001
            ),
            new Issue(
                self::InvalidConstantFQSEN,
                self::CATEGORY_ANALYSIS,
                self::SEVERITY_LOW,
                "'{CONST}' is an invalid FQSEN for a constant",
                self::REMEDIATION_B,
                2002
            ),

            // Issue::CATEGORY_TYPE
            new Issue(
                self::TypeMismatchProperty,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Assigning {TYPE} to property but {PROPERTY} is {TYPE}",
                self::REMEDIATION_B,
                10001
            ),
            new Issue(
                self::PartialTypeMismatchProperty,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Assigning {TYPE} to property but {PROPERTY} is {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10063
            ),
            new Issue(
                self::PossiblyNullTypeMismatchProperty,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Assigning {TYPE} to property but {PROPERTY} is {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10064
            ),
            new Issue(
                self::PossiblyFalseTypeMismatchProperty,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Assigning {TYPE} to property but {PROPERTY} is {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10065
            ),
            new Issue(
                self::TypeMismatchDefault,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Default value for {TYPE} \${VARIABLE} can't be {TYPE}",
                self::REMEDIATION_B,
                10002
            ),
            new Issue(
                self::TypeMismatchVariadicComment,
                self::CATEGORY_TYPE,
                self::SEVERITY_LOW,
                "{PARAMETER} is variadic in comment, but not variadic in param ({PARAMETER})",
                self::REMEDIATION_B,
                10021
            ),
            new Issue(
                self::TypeMismatchVariadicParam,
                self::CATEGORY_TYPE,
                self::SEVERITY_LOW,
                "{PARAMETER} is not variadic in comment, but variadic in param ({PARAMETER})",
                self::REMEDIATION_B,
                10023
            ),
            new Issue(
                self::TypeMismatchArgument,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({VARIABLE}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                10003
            ),
            new Issue(
                self::TypeMismatchArgumentInternal,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({VARIABLE}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE}",
                self::REMEDIATION_B,
                10004
            ),
            new Issue(
                self::TypeMismatchGeneratorYieldValue,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Yield statement has a value with type {TYPE} but {FUNCTIONLIKE} is declared to yield values of type {TYPE} in {TYPE}",
                self::REMEDIATION_B,
                10067
            ),
            new Issue(
                self::TypeMismatchGeneratorYieldKey,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Yield statement has a key with type {TYPE} but {FUNCTIONLIKE} is declared to yield keys of type {TYPE} in {TYPE}",
                self::REMEDIATION_B,
                10068
            ),
            new Issue(
                self::TypeInvalidYieldFrom,
                self::CATEGORY_TYPE,
                self::SEVERITY_CRITICAL,
                "Yield from statement was passed an invalid expression of type {TYPE} (expected Traversable/array)",
                self::REMEDIATION_B,
                10069
            ),
            new Issue(
                self::PartialTypeMismatchArgument,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({VARIABLE}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} ({TYPE} is incompatible) defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                10054
            ),
            new Issue(
                self::PartialTypeMismatchArgumentInternal,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({VARIABLE}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10055
            ),
            new Issue(
                self::PossiblyNullTypeArgument,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({VARIABLE}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} ({TYPE} is incompatible) defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                10056
            ),
            new Issue(
                self::PossiblyNullTypeArgumentInternal,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({VARIABLE}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10057
            ),
            new Issue(
                self::PossiblyFalseTypeArgument,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({VARIABLE}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} ({TYPE} is incompatible) defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                10058
            ),
            new Issue(
                self::PossiblyFalseTypeArgumentInternal,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({VARIABLE}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10059
            ),
            new Issue(
                self::TypeMismatchReturn,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Returning type {TYPE} but {FUNCTIONLIKE} is declared to return {TYPE}",
                self::REMEDIATION_B,
                10005
            ),
            new Issue(
                self::PartialTypeMismatchReturn,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Returning type {TYPE} but {FUNCTIONLIKE} is declared to return {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10060
            ),
            new Issue(
                self::PossiblyNullTypeReturn,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Returning type {TYPE} but {FUNCTIONLIKE} is declared to return {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10061
            ),
            new Issue(
                self::PossiblyFalseTypeReturn,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Returning type {TYPE} but {FUNCTIONLIKE} is declared to return {TYPE} ({TYPE} is incompatible)",
                self::REMEDIATION_B,
                10062
            ),
            new Issue(
                self::TypeMismatchDeclaredReturn,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Doc-block of {METHOD} contains declared return type {TYPE} which is incompatible with the return type {TYPE} declared in the signature",
                self::REMEDIATION_B,
                10020
            ),
            new Issue(
                self::TypeMismatchDeclaredReturnNullable,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Doc-block of {METHOD} has declared return type {TYPE} which is not a permitted replacement of the nullable return type {TYPE} declared in the signature ('?T' should be documented as 'T|null' or '?T')",
                self::REMEDIATION_B,
                10028
            ),
            new Issue(
                self::TypeMismatchDeclaredParam,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Doc-block of \${VARIABLE} in {METHOD} contains phpdoc param type {TYPE} which is incompatible with the param type {TYPE} declared in the signature",
                self::REMEDIATION_B,
                10022
            ),
            new Issue(
                self::TypeMismatchDeclaredParamNullable,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Doc-block of \${VARIABLE} in {METHOD} is phpdoc param type {TYPE} which is not a permitted replacement of the nullable param type {TYPE} declared in the signature ('?T' should be documented as 'T|null' or '?T')",
                self::REMEDIATION_B,
                10027
            ),
            new Issue(
                self::TypeMissingReturn,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Method {METHOD} is declared to return {TYPE} but has no return value",
                self::REMEDIATION_B,
                10006
            ),
            new Issue(
                self::TypeMismatchForeach,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "{TYPE} passed to foreach instead of array",
                self::REMEDIATION_B,
                10007
            ),
            new Issue(
                self::TypeArrayOperator,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid array operand provided to operator '{OPERATOR}' between types {TYPE} and {TYPE}",
                self::REMEDIATION_B,
                10008
            ),
            new Issue(
                self::TypeArraySuspicious,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Suspicious array access to {TYPE}",
                self::REMEDIATION_B,
                10009
            ),
            new Issue(
                self::TypeArrayUnsetSuspicious,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Suspicious attempt to unset an offset of a value of type {TYPE}",
                self::REMEDIATION_B,
                10048
            ),
            new Issue(
                self::TypeComparisonToArray,
                self::CATEGORY_TYPE,
                self::SEVERITY_LOW,
                "{TYPE} to array comparison",
                self::REMEDIATION_B,
                10010
            ),
            new Issue(
                self::TypeComparisonFromArray,
                self::CATEGORY_TYPE,
                self::SEVERITY_LOW,
                "array to {TYPE} comparison",
                self::REMEDIATION_B,
                10011
            ),
            new Issue(
                self::TypeConversionFromArray,
                self::CATEGORY_TYPE,
                self::SEVERITY_LOW,
                "array to {TYPE} conversion",
                self::REMEDIATION_B,
                10012
            ),
            new Issue(
                self::TypeInstantiateAbstract,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Instantiation of abstract class {CLASS}",
                self::REMEDIATION_B,
                10013
            ),
            new Issue(
                self::TypeInstantiateInterface,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Instantiation of interface {INTERFACE}",
                self::REMEDIATION_B,
                10014
            ),
            new Issue(
                self::TypeInvalidClosureScope,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid @phan-closure-scope: expected a class name, got {TYPE}",
                self::REMEDIATION_B,
                10024
            ),
            new Issue(
                self::TypeInvalidRightOperand,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: left operand is array and right is not",
                self::REMEDIATION_B,
                10015
            ),
            new Issue(
                self::TypeInvalidLeftOperand,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: right operand is array and left is not",
                self::REMEDIATION_B,
                10016
            ),
            new Issue(
                self::TypeInvalidRightOperandOfAdd,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: right operand is {TYPE} (expected array or number)",
                self::REMEDIATION_B,
                10070
            ),
            new Issue(
                self::TypeInvalidLeftOperandOfAdd,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: left operand is {TYPE} (expected array or number)",
                self::REMEDIATION_B,
                10071
            ),
            new Issue(
                self::TypeInvalidRightOperandOfNumericOp,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: right operand is {TYPE} (expected number)",
                self::REMEDIATION_B,
                10072
            ),
            new Issue(
                self::TypeInvalidLeftOperandOfNumericOp,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: left operand is {TYPE} (expected number)",
                self::REMEDIATION_B,
                10073
            ),
            new Issue(
                self::TypeInvalidUnaryOperandNumeric,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: unary operand of {STRING_LITERAL} is {TYPE} (expected number)",
                self::REMEDIATION_B,
                10075
            ),
            new Issue(
                self::TypeInvalidUnaryOperandBitwiseNot,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: unary operand of {STRING_LITERAL} is {TYPE} (expected number or string)",
                self::REMEDIATION_B,
                10076
            ),
            new Issue(
                self::TypeParentConstructorCalled,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Must call parent::__construct() from {CLASS} which extends {CLASS}",
                self::REMEDIATION_B,
                10017
            ),
            new Issue(
                self::TypeNonVarPassByRef,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Only variables can be passed by reference at argument {INDEX} of {FUNCTIONLIKE}",
                self::REMEDIATION_B,
                10018
            ),
            new Issue(
                self::NonClassMethodCall,
                self::CATEGORY_TYPE,
                self::SEVERITY_CRITICAL,
                "Call to method {METHOD} on non-class type {TYPE}",
                self::REMEDIATION_B,
                10019
            ),
            new Issue(
                self::TypeVoidAssignment,
                self::CATEGORY_TYPE,
                self::SEVERITY_LOW,
                "Cannot assign void return value",
                self::REMEDIATION_B,
                10000
            ),
            new Issue(
                self::TypeSuspiciousIndirectVariable,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Indirect variable ${(expr)} has invalid inner expression type {TYPE}, expected string/integer',
                self::REMEDIATION_B,
                10025
            ),
            new Issue(
                self::TypeMagicVoidWithReturn,
                self::CATEGORY_TYPE,
                self::SEVERITY_LOW,
                'Found a return statement with a value in the implementation of the magic method {METHOD}, expected void return type',
                self::REMEDIATION_B,
                10026
            ),
            new Issue(
                self::TypeInvalidInstanceof,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Found an instanceof class name of type {TYPE}, but class name must be a valid object or a string',
                self::REMEDIATION_B,
                10029
            ),
            new Issue(
                self::TypeMismatchDimAssignment,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'When appending to a value of type {TYPE}, found an array access index of type {TYPE}, but expected the index to be of type {TYPE}',
                self::REMEDIATION_B,
                10030
            ),
            new Issue(
                self::TypeMismatchDimEmpty,
                self::CATEGORY_TYPE,
                self::SEVERITY_CRITICAL,
                'Assigning to an empty array index of a value of type {TYPE}, but expected the index to exist and be of type {TYPE}',
                self::REMEDIATION_B,
                10031
            ),
            new Issue(
                self::TypeMismatchDimFetch,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'When fetching an array index from a value of type {TYPE}, found an array index of type {TYPE}, but expected the index to be of type {TYPE}',
                self::REMEDIATION_B,
                10032
            ),
            new Issue(
                self::TypeMismatchDimFetchNullable,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'When fetching an array index from a value of type {TYPE}, found an array index of type {TYPE}, but expected the index to be of the non-nullable type {TYPE}',
                self::REMEDIATION_B,
                10044
            ),
            new Issue(
                self::TypeInvalidCallableArraySize,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'In a place where phan was expecting a callable, saw an array of size {COUNT}, but callable arrays must be of size 2',
                self::REMEDIATION_B,
                10033
            ),
            new Issue(
                self::TypeInvalidCallableArrayKey,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'In a place where phan was expecting a callable, saw an array with an unexpected key for element #{INDEX} (expected [$class_or_expr, $method_name])',
                self::REMEDIATION_B,
                10034
            ),
            new Issue(
                self::TypeInvalidCallableObjectOfMethod,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'In a place where phan was expecting a callable, saw a two-element array with a class or expression with an unexpected type {TYPE} (expected a class type or string). Method name was {METHOD}',
                self::REMEDIATION_B,
                10035
            ),
            new Issue(
                self::TypeExpectedObject,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Expected an object instance but saw expression with type {TYPE}',
                self::REMEDIATION_B,
                10036
            ),
            new Issue(
                self::TypeExpectedObjectOrClassName,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Expected an object instance or the name of a class but saw expression with type {TYPE}',
                self::REMEDIATION_B,
                10037
            ),
            new Issue(
                self::TypeExpectedObjectPropAccess,
                self::CATEGORY_TYPE,
                self::SEVERITY_CRITICAL,
                'Expected an object instance when accessing an instance property, but saw an expression with type {TYPE}',
                self::REMEDIATION_B,
                10038
            ),
            new Issue(
                self::TypeExpectedObjectStaticPropAccess,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Expected an object instance or a class name when accessing a static property, but saw an expression with type {TYPE}',
                self::REMEDIATION_B,
                10039
            ),
            new Issue(
                self::TypeExpectedObjectPropAccessButGotNull,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Expected an object instance when accessing an instance property, but saw an expression with type {TYPE}',
                self::REMEDIATION_B,
                10040
            ),
            new Issue(
                self::TypeMismatchUnpackKey,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'When unpacking a value of type {TYPE}, the value\'s keys were of type {TYPE}, but the keys should be consecutive integers starting from 0',
                self::REMEDIATION_B,
                10041
            ),
            new Issue(
                self::TypeMismatchUnpackValue,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Attempting to unpack a value of type {TYPE} which does not contain any subtypes of iterable (such as array or Traversable)',
                self::REMEDIATION_B,
                10042
            ),
            new Issue(
                self::TypeMismatchArrayDestructuringKey,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Attempting an array destructing assignment with a key of type {TYPE} but the only key types of the right-hand side are of type {TYPE}',
                self::REMEDIATION_B,
                10043
            ),
            new Issue(
                self::TypeArraySuspiciousNullable,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Suspicious array access to nullable {TYPE}",
                self::REMEDIATION_B,
                10045
            ),
            new Issue(
                self::TypeInvalidDimOffset,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid offset {SCALAR} of array type {TYPE}",
                self::REMEDIATION_B,
                10046
            ),
            new Issue(
                self::TypeInvalidDimOffsetArrayDestructuring,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid offset {SCALAR} of array type {TYPE} in an array destructuring assignment",
                self::REMEDIATION_B,
                10047
            ),
            new Issue(
                self::TypeInvalidExpressionArrayDestructuring,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid value of type {TYPE} in an array destructuring assignment, expected {TYPE}",
                self::REMEDIATION_B,
                10077
            ),
            new Issue(
                self::TypeSuspiciousEcho,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Suspicious argument {TYPE} for an echo/print statement",
                self::REMEDIATION_B,
                10049
            ),

            new Issue(
                self::TypeInvalidThrowsNonObject,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "@throws annotation of {FUNCTIONLIKE} has invalid non-object type {TYPE}, expected a class",
                self::REMEDIATION_B,
                10050
            ),
            new Issue(
                self::TypeInvalidThrowsIsTrait,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "@throws annotation of {FUNCTIONLIKE} has invalid trait type {TYPE}, expected a class",
                self::REMEDIATION_B,
                10051
            ),
            new Issue(
                self::TypeInvalidThrowsIsInterface,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "@throws annotation of {FUNCTIONLIKE} has suspicious interface type {TYPE} for an @throws annotation, expected class (PHP allows interfaces to be caught, so this might be intentional)",
                self::REMEDIATION_B,
                10052
            ),
            new Issue(
                self::TypeInvalidThrowsNonThrowable,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "@throws annotation of {FUNCTIONLIKE} has suspicious class type {TYPE}, which does not extend Error/Exception",
                self::REMEDIATION_B,
                10053
            ),
            new Issue(
                self::TypeSuspiciousStringExpression,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Suspicious type {TYPE} of a variable or expression used to build a string. (Expected type to be able to cast to a string)",
                self::REMEDIATION_B,
                10066
            ),
            new Issue(
                self::TypeInvalidMethodName,
                self::CATEGORY_TYPE,
                self::SEVERITY_CRITICAL,
                "Instance method name must be a string, got {TYPE}",
                self::REMEDIATION_B,
                10078
            ),
            new Issue(
                self::TypeInvalidStaticMethodName,
                self::CATEGORY_TYPE,
                self::SEVERITY_CRITICAL,
                "Static method name must be a string, got {TYPE}",
                self::REMEDIATION_B,
                10079
            ),
            new Issue(
                self::TypeInvalidCallableMethodName,
                self::CATEGORY_TYPE,
                self::SEVERITY_CRITICAL,
                "Method name of callable must be a string, got {TYPE}",
                self::REMEDIATION_B,
                10080
            ),
            new Issue(
                self::TypeObjectUnsetDeclaredProperty,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Suspicious attempt to unset class {TYPE}'s property {PROPERTY} declared at {FILE}:{LINE} (This can be done, but is more commonly done for dynamic properties and Phan does not expect this)",
                self::REMEDIATION_B,
                10081
            ),
            new Issue(
                self::TypeNoAccessiblePropertiesForeach,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Class {TYPE} was passed to foreach, but it does not extend Traversable and none of its declared properties are accessible from this context. (This check excludes dynamic properties)",
                self::REMEDIATION_B,
                10082
            ),
            new Issue(
                self::TypeNoPropertiesForeach,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Class {TYPE} was passed to foreach, but it does not extend Traversable and doesn't have any declared properties. (This check excludes dynamic properties)",
                self::REMEDIATION_B,
                10083
            ),
            new Issue(
                self::TypeSuspiciousNonTraversableForeach,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Class {TYPE} was passed to foreach, but it does not extend Traversable. This may be intentional, because some of that class's declared properties are accessible from this context. (This check excludes dynamic properties)",
                self::REMEDIATION_B,
                10084
            ),
            new Issue(
                self::TypeInvalidRequire,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Require statement was passed an invalid expression of type {TYPE} (expected a string)",
                self::REMEDIATION_B,
                10085
            ),
            new Issue(
                self::TypeInvalidEval,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Eval statement was passed an invalid expression of type {TYPE} (expected a string)",
                self::REMEDIATION_B,
                10086
            ),
            new Issue(
                self::RelativePathUsed,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "{FUNCTION}() statement was passed a relative path {STRING_LITERAL} instead of an absolute path",
                self::REMEDIATION_B,
                10087
            ),
            new Issue(
                self::TypeInvalidCloneNotObject,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Expected an object to be passed to clone() but got {TYPE}",
                self::REMEDIATION_B,
                10088
            ),
            new Issue(
                self::TypeInvalidTraitReturn,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Expected a class or interface (or built-in type) to be the real return type of {FUNCTIONLIKE} but got trait {TRAIT}",
                self::REMEDIATION_B,
                10089
            ),
            new Issue(
                self::TypeInvalidTraitParam,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "{FUNCTIONLIKE} is declared to have a parameter \${PARAMETER} with a real type of trait {TYPE} (expected a class or interface or built-in type)",
                self::REMEDIATION_B,
                10090
            ),
            new Issue(
                self::TypeInvalidBitwiseBinaryOperator,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid non-int/non-string operand provided to operator '{OPERATOR}' between types {TYPE} and {TYPE}",
                self::REMEDIATION_B,
                10091
            ),
            new Issue(
                self::TypeMismatchBitwiseBinaryOperands,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Unexpected mix of int and string operands provided to operator '{OPERATOR}' between types {TYPE} and {TYPE} (expected one type but not both)",
                self::REMEDIATION_B,
                10092
            ),
            new Issue(
                self::InfiniteRecursion,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "{FUNCTIONLIKE} is calling itself in a way that may cause infinite recursion.",
                self::REMEDIATION_B,
                10093
            ),
            new Issue(
                self::PossiblyNonClassMethodCall,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Call to method {METHOD} on type {TYPE} that could be a non-object",
                self::REMEDIATION_B,
                10094
            ),
            new Issue(
                self::TypeInvalidCallable,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Saw type {TYPE} which cannot be a callable',
                self::REMEDIATION_B,
                10095
            ),
            new Issue(
                self::TypePossiblyInvalidCallable,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Saw type {TYPE} which is possibly not a callable',
                self::REMEDIATION_B,
                10096
            ),
            new Issue(
                self::TypeComparisonToInvalidClass,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Saw code asserting that an expression has a class, but that class is an invalid/impossible FQSEN {STRING_LITERAL}',
                self::REMEDIATION_B,
                10097
            ),
            new Issue(
                self::TypeComparisonToInvalidClassType,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                'Saw code asserting that an expression has a class, but saw an invalid/impossible union type {TYPE} (expected {TYPE})',
                self::REMEDIATION_B,
                10098
            ),
            new Issue(
                self::TypeInvalidUnaryOperandIncOrDec,
                self::CATEGORY_TYPE,
                self::SEVERITY_NORMAL,
                "Invalid operator: unary operand of {STRING_LITERAL} is {TYPE} (expected int or string or float)",
                self::REMEDIATION_B,
                10099
            ),
            // Issue::CATEGORY_VARIABLE
            new Issue(
                self::VariableUseClause,
                self::CATEGORY_VARIABLE,
                self::SEVERITY_NORMAL,
                "Non-variables not allowed within use clause",
                self::REMEDIATION_B,
                12000
            ),

            // Issue::CATEGORY_STATIC
            new Issue(
                self::StaticCallToNonStatic,
                self::CATEGORY_STATIC,
                self::SEVERITY_NORMAL,
                "Static call to non-static method {METHOD} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                9000
            ),
            new Issue(
                self::StaticPropIsStaticType,
                self::CATEGORY_STATIC,
                self::SEVERITY_LOW,
                "Static property {PROPERTY} is declared to have type {TYPE}, but the only instance is shared among all subclasses (Did you mean {TYPE})",
                self::REMEDIATION_A,
                9001
            ),

            // Issue::CATEGORY_CONTEXT
            new Issue(
                self::ContextNotObject,
                self::CATEGORY_CONTEXT,
                self::SEVERITY_CRITICAL,
                "Cannot access {CLASS} when not in object context",
                self::REMEDIATION_B,
                4000
            ),
            new Issue(
                self::ContextNotObjectInCallable,
                self::CATEGORY_CONTEXT,
                self::SEVERITY_NORMAL,
                "Cannot access {CLASS} when not in object context, but code is using callable {METHOD}",
                self::REMEDIATION_B,
                4001
            ),
            new Issue(
                self::ContextNotObjectUsingSelf,
                self::CATEGORY_CONTEXT,
                self::SEVERITY_NORMAL,
                'Cannot use {CLASS} as type when not in object context in {FUNCTION}',
                self::REMEDIATION_B,
                4002
            ),

            // Issue::CATEGORY_DEPRECATED
            new Issue(
                self::DeprecatedFunction,
                self::CATEGORY_DEPRECATED,
                self::SEVERITY_NORMAL,
                "Call to deprecated function {FUNCTIONLIKE} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                5000
            ),
            new Issue(
                self::DeprecatedFunctionInternal,
                self::CATEGORY_DEPRECATED,
                self::SEVERITY_NORMAL,
                "Call to deprecated function {FUNCTIONLIKE}",
                self::REMEDIATION_B,
                5005
            ),
            new Issue(
                self::DeprecatedClass,
                self::CATEGORY_DEPRECATED,
                self::SEVERITY_NORMAL,
                "Call to deprecated class {CLASS} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                5001
            ),
            new Issue(
                self::DeprecatedProperty,
                self::CATEGORY_DEPRECATED,
                self::SEVERITY_NORMAL,
                "Reference to deprecated property {PROPERTY} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                5002
            ),
            new Issue(
                self::DeprecatedInterface,
                self::CATEGORY_DEPRECATED,
                self::SEVERITY_NORMAL,
                "Using a deprecated interface {INTERFACE} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                5003
            ),
            new Issue(
                self::DeprecatedTrait,
                self::CATEGORY_DEPRECATED,
                self::SEVERITY_NORMAL,
                "Using a deprecated trait {TRAIT} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                5004
            ),
            new Issue(
                self::DeprecatedCaseInsensitiveDefine,
                self::CATEGORY_DEPRECATED,
                self::SEVERITY_NORMAL,
                "Creating case-insensitive constants with define() has been deprecated in PHP 7.3",
                self::REMEDIATION_B,
                5006
            ),

            // Issue::CATEGORY_PARAMETER
            new Issue(
                self::ParamReqAfterOpt,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Required argument follows optional",
                self::REMEDIATION_B,
                7000
            ),
            new Issue(
                self::ParamTooMany,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Call with {COUNT} arg(s) to {FUNCTIONLIKE} which only takes {COUNT} arg(s) defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                7001
            ),
            new Issue(
                self::ParamTooManyInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Call with {COUNT} arg(s) to {FUNCTIONLIKE} which only takes {COUNT} arg(s)",
                self::REMEDIATION_B,
                7002
            ),
            new Issue(
                self::ParamTooManyCallable,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Call with {COUNT} arg(s) to {FUNCTIONLIKE} (As a provided callable) which only takes {COUNT} arg(s) defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                7043
            ),
            new Issue(
                self::ParamTooFew,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_CRITICAL,
                "Call with {COUNT} arg(s) to {FUNCTIONLIKE} which requires {COUNT} arg(s) defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                7003
            ),
            new Issue(
                self::ParamTooFewInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_CRITICAL,
                "Call with {COUNT} arg(s) to {FUNCTIONLIKE} which requires {COUNT} arg(s)",
                self::REMEDIATION_B,
                7004
            ),
            new Issue(
                self::ParamTooFewCallable,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_CRITICAL,
                "Call with {COUNT} arg(s) to {FUNCTIONLIKE} (as a provided callable) which requires {COUNT} arg(s) defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                7044
            ),
            new Issue(
                self::ParamSpecial1,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({PARAMETER}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} when argument {INDEX} is {TYPE}",
                self::REMEDIATION_B,
                7005
            ),
            new Issue(
                self::ParamSpecial2,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} ({PARAMETER}) is {TYPE} but {FUNCTIONLIKE} takes {TYPE} when passed only one argument",
                self::REMEDIATION_B,
                7006
            ),
            new Issue(
                self::ParamSpecial3,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "The last argument to {FUNCTIONLIKE} must be of type {TYPE}",
                self::REMEDIATION_B,
                7007
            ),
            new Issue(
                self::ParamSpecial4,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "The second to last argument to {FUNCTIONLIKE} must be of type {TYPE}",
                self::REMEDIATION_B,
                7008
            ),
            new Issue(
                self::ParamTypeMismatch,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Argument {INDEX} is {TYPE} but {FUNCTIONLIKE} takes {TYPE}",
                self::REMEDIATION_B,
                7009
            ),
            new Issue(
                self::ParamSignatureMismatch,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7010
            ),
            new Issue(
                self::ParamSignatureMismatchInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD}",
                self::REMEDIATION_B,
                7011
            ),
            new Issue(
                self::ParamRedefined,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Redefinition of parameter {PARAMETER}",
                self::REMEDIATION_B,
                7012
            ),
            // TODO: Optionally, change the other message to say that it's based off of phpdoc and LSP in a future PR.
            // NOTE: Incompatibilities in the param list are SEVERITY_NORMAL, because the php interpreter emits a notice.
            // Incompatibilities in the return types are SEVERITY_CRITICAL, because the php interpreter will throw an Error.
            new Issue(
                self::ParamSignatureRealMismatchReturnType,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_CRITICAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (method returning '{TYPE}' cannot override method returning '{TYPE}') defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7013
            ),
            new Issue(
                self::ParamSignatureRealMismatchReturnTypeInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_CRITICAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (method returning '{TYPE}' cannot override method returning '{TYPE}')",
                self::REMEDIATION_B,
                7014
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchReturnType,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (method returning '{TYPE}' cannot override method returning '{TYPE}') defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7033
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamType,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (parameter #{INDEX} of type '{TYPE}' cannot replace original parameter of type '{TYPE}') defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7015
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamTypeInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (parameter #{INDEX} of type '{TYPE}' cannot replace original parameter of type '{TYPE}')",
                self::REMEDIATION_B,
                7016
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchParamType,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (parameter #{INDEX} of type '{TYPE}' cannot replace original parameter of type '{TYPE}') defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7034
            ),
            new Issue(
                self::ParamSignatureRealMismatchHasParamType,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_CRITICAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (parameter #{INDEX} has type '{TYPE}' which cannot replace original parameter with no type) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7017
            ),
            new Issue(
                self::ParamSignatureRealMismatchHasParamTypeInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (parameter #{INDEX} has type '{TYPE}' which cannot replace original parameter with no type)",
                self::REMEDIATION_B,
                7018
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchHasParamType,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (parameter #{INDEX} has type '{TYPE}' which cannot replace original parameter with no type) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7035
            ),
            new Issue(
                self::ParamSignatureRealMismatchHasNoParamType,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (parameter #{INDEX} with no type cannot replace original parameter with type '{TYPE}') defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7019
            ),
            new Issue(
                self::ParamSignatureRealMismatchHasNoParamTypeInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (parameter #{INDEX} with no type cannot replace original parameter with type '{TYPE}')",
                self::REMEDIATION_B,
                7020
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchHasNoParamType,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (parameter #{INDEX} with no type cannot replace original parameter with type '{TYPE}') defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7036
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamVariadic,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (parameter #{INDEX} is a variadic parameter replacing a non-variadic parameter) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7021
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamVariadicInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (parameter #{INDEX} is a variadic parameter replacing a non-variadic parameter)",
                self::REMEDIATION_B,
                7022
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchParamVariadic,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (parameter #{INDEX} is a variadic parameter replacing a non-variadic parameter) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7037
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamNotVariadic,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (parameter #{INDEX} is a non-variadic parameter replacing a variadic parameter) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7023
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamNotVariadicInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (parameter #{INDEX} is a non-variadic parameter replacing a variadic parameter)",
                self::REMEDIATION_B,
                7024
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchParamNotVariadic,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (parameter #{INDEX} is a non-variadic parameter replacing a variadic parameter) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7038
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamIsReference,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (parameter #{INDEX} is a reference parameter overriding a non-reference parameter) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7025
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamIsReferenceInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (parameter #{INDEX} is a reference parameter overriding a non-reference parameter)",
                self::REMEDIATION_B,
                7026
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchParamIsReference,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (parameter #{INDEX} is a reference parameter overriding a non-reference parameter) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7039
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamIsNotReference,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (parameter #{INDEX} is a non-reference parameter overriding a reference parameter) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7027
            ),
            new Issue(
                self::ParamSignatureRealMismatchParamIsNotReferenceInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (parameter #{INDEX} is a non-reference parameter overriding a reference parameter)",
                self::REMEDIATION_B,
                7028
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchParamIsNotReference,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (parameter #{INDEX} is a non-reference parameter overriding a reference parameter) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7040
            ),
            new Issue(
                self::ParamSignatureRealMismatchTooFewParameters,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (the method override accepts {COUNT} parameter(s), but the overridden method can accept {COUNT}) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7029
            ),
            new Issue(
                self::ParamSignatureRealMismatchTooFewParametersInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (the method override accepts {COUNT} parameter(s), but the overridden method can accept {COUNT})",
                self::REMEDIATION_B,
                7030
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchTooFewParameters,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (the method override accepts {COUNT} parameter(s), but the overridden method can accept {COUNT}) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7041
            ),
            new Issue(
                self::ParamSignatureRealMismatchTooManyRequiredParameters,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with {METHOD} (the method override requires {COUNT} parameter(s), but the overridden method requires only {COUNT}) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7031
            ),
            new Issue(
                self::ParamSignatureRealMismatchTooManyRequiredParametersInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} should be compatible with internal {METHOD} (the method override requires {COUNT} parameter(s), but the overridden method requires only {COUNT})",
                self::REMEDIATION_B,
                7032
            ),
            new Issue(
                self::ParamSignaturePHPDocMismatchTooManyRequiredParameters,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Declaration of real/@method {METHOD} should be compatible with real/@method {METHOD} (the method override requires {COUNT} parameter(s), but the overridden method requires only {COUNT}) defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                7042
            ),
            new Issue(
                self::ParamSuspiciousOrder,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_NORMAL,
                "Argument #{INDEX} of this call to {FUNCTIONLIKE} is typically a literal or constant but isn't, but argument #{INDEX} (which is typically a variable) is a literal or constant. The arguments may be in the wrong order.",
                self::REMEDIATION_B,
                7045
            ),
            new Issue(
                self::ParamTooManyUnpack,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Call with {COUNT} or more args to {FUNCTIONLIKE} which only takes {COUNT} arg(s) defined at {FILE}:{LINE} (argument unpacking was used)",
                self::REMEDIATION_B,
                7046
            ),
            new Issue(
                self::ParamTooManyUnpackInternal,
                self::CATEGORY_PARAMETER,
                self::SEVERITY_LOW,
                "Call with {COUNT} or more args to {FUNCTIONLIKE} which only takes {COUNT} arg(s) (argument unpacking was used)",
                self::REMEDIATION_B,
                7047
            ),

            // Issue::CATEGORY_NOOP
            new Issue(
                self::NoopProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused property",
                self::REMEDIATION_B,
                6000
            ),
            new Issue(
                self::NoopArray,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused array",
                self::REMEDIATION_B,
                6001
            ),
            new Issue(
                self::NoopConstant,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused constant",
                self::REMEDIATION_B,
                6002
            ),
            new Issue(
                self::NoopClosure,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused closure",
                self::REMEDIATION_B,
                6003
            ),
            new Issue(
                self::NoopVariable,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused variable",
                self::REMEDIATION_B,
                6004
            ),
            new Issue(
                self::NoopUnaryOperator,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused result of a unary '{OPERATOR}' operator",
                self::REMEDIATION_B,
                6020
            ),
            new Issue(
                self::NoopBinaryOperator,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused result of a binary '{OPERATOR}' operator",
                self::REMEDIATION_B,
                6021
            ),
            new Issue(
                self::NoopStringLiteral,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused result of a string literal {STRING_LITERAL} near this line",
                self::REMEDIATION_B,
                6029
            ),
            new Issue(
                self::NoopEncapsulatedStringLiteral,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused result of an encapsulated string literal",
                self::REMEDIATION_B,
                6030
            ),
            new Issue(
                self::NoopNumericLiteral,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                "Unused result of a numeric literal {STRING_LITERAL} near this line",
                self::REMEDIATION_B,
                6031
            ),
            new Issue(
                self::UnreferencedClass,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to class {CLASS}",
                self::REMEDIATION_B,
                6005
            ),
            new Issue(
                self::UnreferencedConstant,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to global constant {CONST}",
                self::REMEDIATION_B,
                6008
            ),
            new Issue(
                self::UnreferencedFunction,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to function {FUNCTION}",
                self::REMEDIATION_B,
                6009
            ),
            new Issue(
                self::UnreferencedClosure,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to {FUNCTION}",
                self::REMEDIATION_B,
                6010
            ),
            new Issue(
                self::UnreferencedPublicMethod,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to public method {METHOD}",
                self::REMEDIATION_B,
                6011
            ),
            new Issue(
                self::UnreferencedProtectedMethod,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to protected method {METHOD}",
                self::REMEDIATION_B,
                6012
            ),
            new Issue(
                self::UnreferencedPrivateMethod,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to private method {METHOD}",
                self::REMEDIATION_B,
                6013
            ),
            new Issue(
                self::UnreferencedPublicProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to public property {PROPERTY}",
                self::REMEDIATION_B,
                6014
            ),
            new Issue(
                self::UnreferencedProtectedProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to protected property {PROPERTY}",
                self::REMEDIATION_B,
                6015
            ),
            new Issue(
                self::UnreferencedPrivateProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to private property {PROPERTY}",
                self::REMEDIATION_B,
                6016
            ),
            new Issue(
                self::ReadOnlyPublicProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero write references to public property {PROPERTY}",
                self::REMEDIATION_B,
                6032
            ),
            new Issue(
                self::ReadOnlyProtectedProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero write references to protected property {PROPERTY}",
                self::REMEDIATION_B,
                6033
            ),
            new Issue(
                self::ReadOnlyPrivateProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero write references to private property {PROPERTY}",
                self::REMEDIATION_B,
                6034
            ),
            new Issue(
                self::WriteOnlyPublicProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero read references to public property {PROPERTY}",
                self::REMEDIATION_B,
                6025
            ),
            new Issue(
                self::WriteOnlyProtectedProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero read references to protected property {PROPERTY}",
                self::REMEDIATION_B,
                6026
            ),
            new Issue(
                self::WriteOnlyPrivateProperty,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero read references to private property {PROPERTY}",
                self::REMEDIATION_B,
                6027
            ),
            new Issue(
                self::UnreferencedPublicClassConstant,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to public class constant {CONST}",
                self::REMEDIATION_B,
                6017
            ),
            new Issue(
                self::UnreferencedProtectedClassConstant,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to protected class constant {CONST}",
                self::REMEDIATION_B,
                6018
            ),
            new Issue(
                self::UnreferencedPrivateClassConstant,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to public class constant {CONST}",
                self::REMEDIATION_B,
                6019
            ),
            new Issue(
                self::UnreferencedUseNormal,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to use statement for classlike/namespace {CLASSLIKE} ({CLASSLIKE})",
                self::REMEDIATION_B,
                6022
            ),
            new Issue(
                self::UnreferencedUseFunction,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to use statement for function {FUNCTION} ({FUNCTION})",
                self::REMEDIATION_B,
                6023
            ),
            new Issue(
                self::UnreferencedUseConstant,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Possibly zero references to use statement for constant {CONST} ({CONST})",
                self::REMEDIATION_B,
                6024
            ),
            new Issue(
                self::UnreachableCatch,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                "Catch statement for {CLASSLIKE} is unreachable. An earlier catch statement at line {LINE} caught the ancestor class/interface {CLASSLIKE}",
                self::REMEDIATION_B,
                6028
            ),
            new Issue(
                self::UnusedVariable,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Unused definition of variable ${VARIABLE}',
                self::REMEDIATION_B,
                6035
            ),
            new Issue(
                self::UnusedPublicMethodParameter,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Parameter ${PARAMETER} is never used',
                self::REMEDIATION_B,
                6036
            ),
            new Issue(
                self::UnusedPublicFinalMethodParameter,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Parameter ${PARAMETER} is never used',
                self::REMEDIATION_B,
                6037
            ),
            new Issue(
                self::UnusedProtectedMethodParameter,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Parameter ${PARAMETER} is never used',
                self::REMEDIATION_B,
                6038
            ),
            new Issue(
                self::UnusedProtectedFinalMethodParameter,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Parameter ${PARAMETER} is never used',
                self::REMEDIATION_B,
                6039
            ),
            new Issue(
                self::UnusedPrivateMethodParameter,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Parameter ${PARAMETER} is never used',
                self::REMEDIATION_B,
                6040
            ),
            new Issue(
                self::UnusedPrivateFinalMethodParameter,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Parameter ${PARAMETER} is never used',
                self::REMEDIATION_B,
                6041
            ),
            new Issue(
                self::UnusedClosureUseVariable,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Closure use variable ${VARIABLE} is never used',
                self::REMEDIATION_B,
                6042
            ),
            new Issue(
                self::UnusedClosureParameter,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Parameter ${PARAMETER} is never used',
                self::REMEDIATION_B,
                6043
            ),
            new Issue(
                self::UnusedGlobalFunctionParameter,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'Parameter ${PARAMETER} is never used',
                self::REMEDIATION_B,
                6044
            ),
            new Issue(
                self::UnusedVariableValueOfForeachWithKey,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                'Unused definition of variable ${VARIABLE} as the value of a foreach loop that included keys',
                self::REMEDIATION_B,
                6045
            ),
            new Issue(
                self::UnusedVariableCaughtException,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                'Unused definition of variable ${VARIABLE} as a caught exception',
                self::REMEDIATION_B,
                6046
            ),
            new Issue(
                self::UseNormalNamespacedNoEffect,
                self::CATEGORY_NOOP,
                self::SEVERITY_LOW,
                'The use statement for class/namespace {CLASS} in a namespace has no effect',
                self::REMEDIATION_A,
                6047
            ),
            new Issue(
                self::UseNormalNoEffect,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'The use statement for class/namespace {CLASS} in the global namespace has no effect',
                self::REMEDIATION_A,
                6048
            ),
            new Issue(
                self::UseFunctionNoEffect,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'The use statement for function {FUNCTION} has no effect',
                self::REMEDIATION_A,
                6049
            ),
            new Issue(
                self::UseContantNoEffect,
                self::CATEGORY_NOOP,
                self::SEVERITY_NORMAL,
                'The use statement for constant {CONST} has no effect',
                self::REMEDIATION_A,
                6050
            ),

            // Issue::CATEGORY_REDEFINE
            new Issue(
                self::RedefineClass,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "{CLASS} defined at {FILE}:{LINE} was previously defined as {CLASS} at {FILE}:{LINE}",
                self::REMEDIATION_B,
                8000
            ),
            new Issue(
                self::RedefineClassInternal,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "{CLASS} defined at {FILE}:{LINE} was previously defined as {CLASS} internally",
                self::REMEDIATION_B,
                8001
            ),
            new Issue(
                self::RedefineFunction,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "Function {FUNCTION} defined at {FILE}:{LINE} was previously defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                8002
            ),
            new Issue(
                self::RedefineFunctionInternal,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "Function {FUNCTION} defined at {FILE}:{LINE} was previously defined internally",
                self::REMEDIATION_B,
                8003
            ),
            new Issue(
                self::IncompatibleCompositionProp,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "{TRAIT} and {TRAIT} define the same property ({PROPERTY}) in the composition of {CLASS}. However, the definition differs and is considered incompatible. Class was composed in {FILE} on line {LINE}",
                self::REMEDIATION_B,
                8004
            ),
            new Issue(
                self::IncompatibleCompositionMethod,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "Declaration of {METHOD} must be compatible with {METHOD} in {FILE} on line {LINE}",
                self::REMEDIATION_B,
                8005
            ),
            new Issue(
                self::RedefineClassAlias,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "{CLASS} aliased at {FILE}:{LINE} was previously defined as {CLASS} at {FILE}:{LINE}",
                self::REMEDIATION_B,
                8006
            ),
            new Issue(
                self::RedefinedUsedTrait,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "{CLASS} uses {TRAIT} declared at {FILE}:{LINE} which is also declared at {FILE}:{LINE}. This may lead to confusing errors.",
                self::REMEDIATION_B,
                8007
            ),
            new Issue(
                self::RedefinedInheritedInterface,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "{CLASS} inherits {INTERFACE} declared at {FILE}:{LINE} which is also declared at {FILE}:{LINE}. This may lead to confusing errors.",
                self::REMEDIATION_B,
                8008
            ),
            new Issue(
                self::RedefinedExtendedClass,
                self::CATEGORY_REDEFINE,
                self::SEVERITY_NORMAL,
                "{CLASS} extends {CLASS} declared at {FILE}:{LINE} which is also declared at {FILE}:{LINE}. This may lead to confusing errors.",
                self::REMEDIATION_B,
                8009
            ),

            // Issue::CATEGORY_ACCESS
            new Issue(
                self::AccessPropertyProtected,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot access protected property {PROPERTY} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1000
            ),
            new Issue(
                self::AccessPropertyPrivate,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot access private property {PROPERTY} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1001
            ),
            new Issue(
                self::AccessReadOnlyProperty,
                self::CATEGORY_ACCESS,
                self::SEVERITY_LOW,
                "Cannot modify read-only property {PROPERTY} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1028
            ),
            new Issue(
                self::AccessWriteOnlyProperty,
                self::CATEGORY_ACCESS,
                self::SEVERITY_LOW,
                "Cannot read write-only property {PROPERTY} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1029
            ),
            new Issue(
                self::AccessReadOnlyMagicProperty,
                self::CATEGORY_ACCESS,
                self::SEVERITY_NORMAL,
                "Cannot modify read-only magic property {PROPERTY} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1030
            ),
            new Issue(
                self::AccessWriteOnlyMagicProperty,
                self::CATEGORY_ACCESS,
                self::SEVERITY_NORMAL,
                "Cannot read write-only magic property {PROPERTY} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1031
            ),
            new Issue(
                self::AccessMethodProtected,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot access protected method {METHOD} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1002
            ),
            new Issue(
                self::AccessMethodPrivate,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot access private method {METHOD} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1003
            ),
            new Issue(
                self::AccessSignatureMismatch,
                self::CATEGORY_ACCESS,
                self::SEVERITY_NORMAL,
                "Access level to {METHOD} must be compatible with {METHOD} defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                1004
            ),
            new Issue(
                self::AccessSignatureMismatchInternal,
                self::CATEGORY_ACCESS,
                self::SEVERITY_NORMAL,
                "Access level to {METHOD} must be compatible with internal {METHOD}",
                self::REMEDIATION_B,
                1005
            ),
            new Issue(
                self::PropertyAccessSignatureMismatch,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Access level to {PROPERTY} must be compatible with {PROPERTY} defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                1022
            ),
            new Issue(
                self::PropertyAccessSignatureMismatchInternal,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Access level to {PROPERTY} must be compatible with internal {PROPERTY}",
                self::REMEDIATION_B,
                1023
            ),
            new Issue(
                self::AccessConstantSignatureMismatch,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Access level to {CONST} must be compatible with {CONST} defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                1024
            ),
            new Issue(
                self::AccessConstantSignatureMismatchInternal,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Access level to {CONST} must be compatible with internal {CONST}",
                self::REMEDIATION_B,
                1025
            ),
            new Issue(
                self::AccessStaticToNonStaticProperty,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot make static property {PROPERTY} into the non static property {PROPERTY}",
                self::REMEDIATION_B,
                1026
            ),
            new Issue(
                self::AccessNonStaticToStaticProperty,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot make non static property {PROPERTY} into the static property {PROPERTY}",
                self::REMEDIATION_B,
                1027
            ),
            new Issue(
                self::AccessStaticToNonStatic,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot make static method {METHOD}() non static",
                self::REMEDIATION_B,
                1006
            ),
            new Issue(
                self::AccessNonStaticToStatic,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot make non static method {METHOD}() static",
                self::REMEDIATION_B,
                1007
            ),
            new Issue(
                self::AccessClassConstantPrivate,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot access private class constant {CONST} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1008
            ),
            new Issue(
                self::AccessClassConstantProtected,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot access protected class constant {CONST} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1009
            ),
            new Issue(
                self::AccessPropertyStaticAsNonStatic,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Accessing static property {PROPERTY} as non static",
                self::REMEDIATION_B,
                1010
            ),
            new Issue(
                self::AccessOwnConstructor,
                self::CATEGORY_ACCESS,
                self::SEVERITY_NORMAL,
                "Accessing own constructor directly via {CLASS}::__construct",
                self::REMEDIATION_B,
                1020
            ),
            new Issue(
                self::AccessMethodProtectedWithCallMagicMethod,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot access protected method {METHOD} defined at {FILE}:{LINE} (if this call should be handled by __call, consider adding a @method tag to the class)",
                self::REMEDIATION_B,
                1011
            ),
            new Issue(
                self::AccessMethodPrivateWithCallMagicMethod,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Cannot access private method {METHOD} defined at {FILE}:{LINE} (if this call should be handled by __call, consider adding a @method tag to the class)",
                self::REMEDIATION_B,
                1012
            ),
            new Issue(
                self::AccessWrongInheritanceCategory,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Attempting to inherit {CLASSLIKE} defined at {FILE}:{LINE} as if it were a {CLASSLIKE}",
                self::REMEDIATION_B,
                1013
            ),
            new Issue(
                self::AccessWrongInheritanceCategoryInternal,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Attempting to inherit internal {CLASSLIKE} as if it were a {CLASSLIKE}",
                self::REMEDIATION_B,
                1014
            ),
            new Issue(
                self::AccessExtendsFinalClass,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Attempting to extend from final class {CLASS} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                1015
            ),
            new Issue(
                self::AccessExtendsFinalClassInternal,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Attempting to extend from final internal class {CLASS}",
                self::REMEDIATION_B,
                1016
            ),
            new Issue(
                self::AccessOverridesFinalMethod,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Declaration of method {METHOD} overrides final method {METHOD} defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                1017
            ),
            new Issue(
                self::AccessOverridesFinalMethodInternal,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Declaration of method {METHOD} overrides final internal method {METHOD}",
                self::REMEDIATION_B,
                1018
            ),
            new Issue(
                self::AccessOverridesFinalMethodPHPDoc,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Declaration of phpdoc method {METHOD} is an unnecessary override of final method {METHOD} defined in {FILE}:{LINE}",
                self::REMEDIATION_B,
                1019
            ),
            new Issue(
                self::AccessPropertyNonStaticAsStatic,
                self::CATEGORY_ACCESS,
                self::SEVERITY_CRITICAL,
                "Accessing non static property {PROPERTY} as static",
                self::REMEDIATION_B,
                1021
            ),

            // Issue::CATEGORY_COMPATIBLE
            new Issue(
                self::CompatiblePHP7,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_NORMAL,
                "Expression may not be PHP 7 compatible",
                self::REMEDIATION_B,
                3000
            ),
            new Issue(
                self::CompatibleExpressionPHP7,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_NORMAL,
                "{CLASS} expression may not be PHP 7 compatible",
                self::REMEDIATION_B,
                3001
            ),
            new Issue(
                self::CompatibleNullableTypePHP70,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Nullable type '{TYPE}' is not compatible with PHP 7.0",
                self::REMEDIATION_B,
                3002
            ),
            new Issue(
                self::CompatibleShortArrayAssignPHP70,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Square bracket syntax for an array destructuring assignment is not compatible with PHP 7.0",
                self::REMEDIATION_A,
                3003
            ),
            new Issue(
                self::CompatibleKeyedArrayAssignPHP70,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Using array keys in an array destructuring assignment is not compatible with PHP 7.0",
                self::REMEDIATION_B,
                3004
            ),
            new Issue(
                self::CompatibleVoidTypePHP70,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Return type '{TYPE}' means the absence of a return value starting in PHP 7.1. In PHP 7.0, void refers to a class/interface with the name 'void'",
                self::REMEDIATION_B,
                3005
            ),
            new Issue(
                self::CompatibleIterableTypePHP70,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Return type '{TYPE}' means a Traversable/array value starting in PHP 7.1. In PHP 7.0, iterable refers to a class/interface with the name 'iterable'",
                self::REMEDIATION_B,
                3006
            ),
            new Issue(
                self::CompatibleObjectTypePHP71,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Type '{TYPE}' refers to any object starting in PHP 7.2. In PHP 7.1 and earlier, it refers to a class/interface with the name 'object'",
                self::REMEDIATION_B,
                3007
            ),
            new Issue(
                self::CompatibleUseVoidPHP70,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Using '{TYPE}' as void will be a syntax error in PHP 7.1 (void becomes the absence of a return type).",
                self::REMEDIATION_B,
                3008
            ),
            new Issue(
                self::CompatibleUseIterablePHP71,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Using '{TYPE}' as iterable will be a syntax error in PHP 7.2 (iterable becomes a native type with subtypes Array and Iterator).",
                self::REMEDIATION_B,
                3009
            ),
            new Issue(
                self::CompatibleUseObjectPHP71,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Using '{TYPE}' as object will be a syntax error in PHP 7.2 (object becomes a native type that accepts any class instance).",
                self::REMEDIATION_B,
                3010
            ),
            new Issue(
                self::CompatibleMultiExceptionCatchPHP70,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_CRITICAL,
                "Catching multiple exceptions is not supported before PHP 7.1",
                self::REMEDIATION_B,
                3011
            ),
            new Issue(
                self::CompatibleNegativeStringOffset,
                self::CATEGORY_COMPATIBLE,
                self::SEVERITY_NORMAL,
                "Using negative string offsets is not supported before PHP 7.1 (emits an 'Uninitialized string offset' notice)",
                self::REMEDIATION_B,
                3012
            ),

            // Issue::CATEGORY_GENERIC
            new Issue(
                self::TemplateTypeConstant,
                self::CATEGORY_GENERIC,
                self::SEVERITY_NORMAL,
                "constant {CONST} may not have a template type",
                self::REMEDIATION_B,
                14000
            ),
            new Issue(
                self::TemplateTypeStaticMethod,
                self::CATEGORY_GENERIC,
                self::SEVERITY_NORMAL,
                "static method {METHOD} may not use template types",
                self::REMEDIATION_B,
                14001
            ),
            new Issue(
                self::TemplateTypeStaticProperty,
                self::CATEGORY_GENERIC,
                self::SEVERITY_NORMAL,
                "static property {PROPERTY} may not have a template type",
                self::REMEDIATION_B,
                14002
            ),
            new Issue(
                self::GenericGlobalVariable,
                self::CATEGORY_GENERIC,
                self::SEVERITY_NORMAL,
                "Global variable {VARIABLE} may not be assigned an instance of a generic class",
                self::REMEDIATION_B,
                14003
            ),
            new Issue(
                self::GenericConstructorTypes,
                self::CATEGORY_GENERIC,
                self::SEVERITY_NORMAL,
                "Missing template parameter for type {TYPE} on constructor for generic class {CLASS}",
                self::REMEDIATION_B,
                14004
            ),
            new Issue(
                self::TemplateTypeNotUsedInFunctionReturn,
                self::CATEGORY_GENERIC,
                self::SEVERITY_NORMAL,
                "Template type {TYPE} not used in return value of function/method {FUNCTIONLIKE}",
                self::REMEDIATION_B,
                14005
            ),
            new Issue(
                self::TemplateTypeNotDeclaredInFunctionParams,
                self::CATEGORY_GENERIC,
                self::SEVERITY_NORMAL,
                "Template type {TYPE} not declared in parameters of function/method {FUNCTIONLIKE} (or Phan can't extract template types for this use case)",
                self::REMEDIATION_B,
                14006
            ),

            // Issue::CATEGORY_INTERNAL
            new Issue(
                self::AccessConstantInternal,
                self::CATEGORY_INTERNAL,
                self::SEVERITY_NORMAL,
                "Cannot access internal constant {CONST} of namespace {NAMESPACE} defined at {FILE}:{LINE} from namespace {NAMESPACE}",
                self::REMEDIATION_B,
                15000
            ),
            new Issue(
                self::AccessClassInternal,
                self::CATEGORY_INTERNAL,
                self::SEVERITY_NORMAL,
                "Cannot access internal {CLASS} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                15001
            ),
            new Issue(
                self::AccessClassConstantInternal,
                self::CATEGORY_INTERNAL,
                self::SEVERITY_NORMAL,
                "Cannot access internal class constant {CONST} defined at {FILE}:{LINE}",
                self::REMEDIATION_B,
                15002
            ),
            new Issue(
                self::AccessPropertyInternal,
                self::CATEGORY_INTERNAL,
                self::SEVERITY_NORMAL,
                "Cannot access internal property {PROPERTY} of namespace {NAMESPACE} defined at {FILE}:{LINE} from namespace {NAMESPACE}",
                self::REMEDIATION_B,
                15003
            ),
            new Issue(
                self::AccessMethodInternal,
                self::CATEGORY_INTERNAL,
                self::SEVERITY_NORMAL,
                "Cannot access internal method {METHOD} of namespace {NAMESPACE} defined at {FILE}:{LINE} from namespace {NAMESPACE}",
                self::REMEDIATION_B,
                15004
            ),

            // Issue::CATEGORY_COMMENT
            new Issue(
                self::InvalidCommentForDeclarationType,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "The phpdoc comment for {COMMENT} cannot occur on a {TYPE}",
                self::REMEDIATION_B,
                16000
            ),
            new Issue(
                self::MisspelledAnnotation,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw misspelled annotation {COMMENT}, should be one of {COMMENT}",
                self::REMEDIATION_B,
                16001
            ),
            new Issue(
                self::UnextractableAnnotation,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw unextractable annotation for comment '{COMMENT}'",
                self::REMEDIATION_B,
                16002
            ),
            new Issue(
                self::UnextractableAnnotationPart,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw unextractable annotation for a fragment of comment '{COMMENT}': '{COMMENT}'",
                self::REMEDIATION_B,
                16003
            ),
            new Issue(
                self::UnextractableAnnotationSuffix,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw a token Phan may have failed to parse after '{COMMENT}': after {TYPE}, saw '{COMMENT}'",
                self::REMEDIATION_B,
                16009
            ),
            new Issue(
                self::UnextractableAnnotationElementName,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw possibly unextractable annotation for a fragment of comment '{COMMENT}': after {TYPE}, did not see an element name (will guess based on comment order)",
                self::REMEDIATION_B,
                16010
            ),
            new Issue(
                self::CommentParamWithoutRealParam,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw an @param annotation for {VARIABLE}, but it was not found in the param list of {FUNCTIONLIKE}",
                self::REMEDIATION_B,
                16004
            ),
            new Issue(
                self::CommentParamAssertionWithoutRealParam,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw an @phan-assert annotation for {VARIABLE}, but it was not found in the param list of {FUNCTIONLIKE}",
                self::REMEDIATION_B,
                16019
            ),
            new Issue(
                self::CommentParamOnEmptyParamList,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw an @param annotation for {VARIABLE}, but the param list of {FUNCTIONLIKE} is empty",
                self::REMEDIATION_B,
                16005
            ),
            new Issue(
                self::CommentOverrideOnNonOverrideMethod,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw an @override annotation for method {METHOD}, but could not find an overridden method and it is not a magic method",
                self::REMEDIATION_B,
                16006
            ),
            new Issue(
                self::CommentOverrideOnNonOverrideConstant,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Saw an @override annotation for class constant {CONST}, but could not find an overridden constant",
                self::REMEDIATION_B,
                16007
            ),
            new Issue(
                self::CommentParamOutOfOrder,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Expected @param annotation for {VARIABLE} to be before the @param annotation for {VARIABLE}",
                self::REMEDIATION_A,
                16008
            ),
            new Issue(
                self::ThrowTypeAbsent,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "{FUNCTIONLIKE} can throw {TYPE} here, but has no '@throws' declarations for that class",
                self::REMEDIATION_A,
                16011
            ),
            new Issue(
                self::ThrowTypeAbsentForCall,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "{FUNCTIONLIKE} can throw {TYPE} because it calls {FUNCTIONLIKE}, but has no '@throws' declarations for that class",
                self::REMEDIATION_A,
                16012
            ),
            new Issue(
                self::ThrowTypeMismatch,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "{FUNCTIONLIKE} throws {TYPE}, but it only has declarations of '@throws {TYPE}'",
                self::REMEDIATION_A,
                16013
            ),
            new Issue(
                self::ThrowTypeMismatchForCall,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "{FUNCTIONLIKE} throws {TYPE} because it calls {FUNCTIONLIKE}, but it only has declarations of '@throws {TYPE}'",
                self::REMEDIATION_A,
                16014
            ),
            new Issue(
                self::CommentAmbiguousClosure,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                "Comment {STRING_LITERAL} refers to {TYPE} instead of \\Closure - Assuming \\Closure",
                self::REMEDIATION_A,
                16015
            ),
            new Issue(
                self::CommentDuplicateParam,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                'Comment declares @param ${PARAMETER} multiple times',
                self::REMEDIATION_A,
                16016
            ),
            new Issue(
                self::CommentDuplicateMagicProperty,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                'Comment declares @property* ${PROPERTY} multiple times',
                self::REMEDIATION_A,
                16017
            ),
            // TODO: Support declaring both instance and static methods of the same name
            new Issue(
                self::CommentDuplicateMagicMethod,
                self::CATEGORY_COMMENT,
                self::SEVERITY_LOW,
                'Comment declares @method {METHOD} multiple times',
                self::REMEDIATION_A,
                16018
            ),
        ];
        // phpcs:enable Generic.Files.LineLength

        self::sanityCheckErrorList($error_list);
        // Verified the error meets preconditions, now add it.
        foreach ($error_list as $error) {
            $error_type = $error->getType();
            $error_map[$error_type] = $error;
        }

        return $error_map;
    }

    private static function getNextTypeId(array $error_list, int $invalid_type_id) : int
    {
        for ($id = $invalid_type_id + 1; true; $id++) {
            foreach ($error_list as $error) {
                if ($error->getTypeId() === $id) {
                    continue 2;
                }
            }
            return $id;
        }
    }

    /**
     * @param array<int,Issue> $error_list
     * @return void
     */
    private static function sanityCheckErrorList(array $error_list)
    {
        $error_map = [];
        $unique_type_id_set = [];
        foreach ($error_list as $error) {
            $error_type = $error->getType();
            if (\array_key_exists($error_type, $error_map)) {
                throw new AssertionError("Issue of type $error_type has multiple definitions");
            }

            if (\strncmp($error_type, 'Phan', 4) !== 0) {
                throw new AssertionError("Issue of type $error_type should begin with 'Phan'");
            }

            $error_type_id = $error->getTypeId();
            if (\array_key_exists($error_type_id, $unique_type_id_set)) {
                throw new AssertionError("Multiple issues exist with pylint error id $error_type_id - The next available id is " .
                    self::getNextTypeId($error_list, $error_type_id));
            }
            $unique_type_id_set[$error_type_id] = $error;
            $category = $error->getCategory();
            $expected_category_for_type_id_bitpos = (int)floor($error_type_id / 1000);
            $expected_category_for_type_id = 1 << $expected_category_for_type_id_bitpos;
            if ($category !== $expected_category_for_type_id) {
                throw new AssertionError(sprintf(
                    "Expected error %s of type %d to be category %d(1<<%d), got 1<<%d\n",
                    $error_type,
                    $error_type_id,
                    $category,
                    (int)round(log($category, 2)),
                    $expected_category_for_type_id_bitpos
                ));
            }
            $error_map[$error_type] = $error;
        }
    }


    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return int (Unique integer code corresponding to getType())
     */
    public function getTypeId() : int
    {
        return $this->type_id;
    }

    /**
     * @return int
     */
    public function getCategory() : int
    {
        return $this->category;
    }

    /**
     * @return string
     * The name of this issue's category
     */
    public function getCategoryName() : string
    {
        return self::getNameForCategory($this->getCategory());
    }

    /**
     * @return string
     * The name of the category
     */
    public static function getNameForCategory(int $category) : string
    {
        return self::CATEGORY_NAME[$category] ?? '';
    }

    /**
     * @return int
     */
    public function getSeverity() : int
    {
        return $this->severity;
    }

    /**
     * @return string
     * A descriptive name of the severity of the issue
     */
    public function getSeverityName() : string
    {
        switch ($this->getSeverity()) {
            case self::SEVERITY_LOW:
                return 'low';
            case self::SEVERITY_NORMAL:
                return 'normal';
            case self::SEVERITY_CRITICAL:
                return 'critical';
            default:
                throw new \AssertionError('Unknown severity ' . $this->getSeverity());
        }
    }

    /**
     * @return int
     * @suppress PhanUnreferencedPublicMethod (no reporters use this right now)
     */
    public function getRemediationDifficulty() : int
    {
        return $this->remediation_difficulty;
    }

    /**
     * @return string
     */
    public function getTemplate() : string
    {
        return $this->template;
    }

    /**
     * @return string - template with the information needed to colorize this.
     */
    public function getTemplateRaw() : string
    {
        return $this->template_raw;
    }

    /**
     * @param array<int,mixed> $template_parameters
     * @return IssueInstance
     */
    public function __invoke(
        string $file,
        int $line,
        array $template_parameters = [],
        Suggestion $suggestion = null
    ) : IssueInstance {
        // TODO: Add callable to expanded union types instead
        return new IssueInstance(
            $this,
            $file,
            $line,
            $template_parameters,
            $suggestion
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromType(string $type) : Issue
    {
        $error_map = self::issueMap();

        if (!isset($error_map[$type])) {
            throw new InvalidArgumentException("Undefined error type $type");
        }

        return $error_map[$type];
    }

    /**
     * @param string $type
     * The type of the issue
     *
     * @param string $file
     * The name of the file where the issue was found
     *
     * @param int $line
     * The line number (start) where the issue was found
     *
     * @param string|int|float|bool|Type|UnionType|FQSEN|TypedElement|UnaddressableTypedElement ...$template_parameters
     * Any template parameters required for the issue
     * message
     *
     * @return void
     * @suppress PhanUnreferencedPublicMethod
     */
    public static function emit(
        string $type,
        string $file,
        int $line,
        ...$template_parameters
    ) {
        self::emitWithParameters(
            $type,
            $file,
            $line,
            $template_parameters
        );
    }

    /**
     * @param string $type
     * The type of the issue
     *
     * @param string $file
     * The name of the file where the issue was found
     *
     * @param int $line
     * The line number (start) where the issue was found
     *
     * @param array<int,string|int|float|bool|Type|UnionType|FQSEN|TypedElement|UnaddressableTypedElement> $template_parameters
     * Any template parameters required for the issue
     * message
     *
     * @param ?Suggestion $suggestion (optional details on fixing this)
     *
     * @return void
     */
    public static function emitWithParameters(
        string $type,
        string $file,
        int $line,
        array $template_parameters,
        Suggestion $suggestion = null
    ) {
        $issue = self::fromType($type);

        self::emitInstance(
            $issue($file, $line, $template_parameters, $suggestion)
        );
    }

    /**
     * @param IssueInstance $issue_instance
     * An issue instance to emit
     *
     * @return void
     */
    public static function emitInstance(
        IssueInstance $issue_instance
    ) {
        Phan::getIssueCollector()->collectIssue($issue_instance);
    }

    /**
     * @param CodeBase $code_base
     * The code base within which we're operating
     *
     * @param Context $context
     * The context in which the instance was found
     *
     * @param IssueInstance $issue_instance
     * An issue instance to emit
     *
     * @return void
     */
    public static function maybeEmitInstance(
        CodeBase $code_base,
        Context $context,
        IssueInstance $issue_instance
    ) {
        // If this issue type has been suppressed in
        // the config, ignore it

        $issue = $issue_instance->getIssue();
        if (self::shouldSuppressIssue(
            $code_base,
            $context,
            $issue->getType(),
            $issue_instance->getLine(),
            $issue_instance->getTemplateParameters(),
            $issue_instance->getSuggestion()
        )) {
            return;
        }

        self::emitInstance($issue_instance);
    }

    /**
     * @param CodeBase $code_base
     * The code base within which we're operating
     *
     * @param Context $context
     * The context in which the node we're going to be looking
     * at exits.
     *
     * @param string $issue_type
     * The type of issue to emit such as Issue::ParentlessClass
     *
     * @param int $lineno
     * The line number where the issue was found
     *
     * @param string|int|float|bool|Type|UnionType|FQSEN|TypedElement|UnaddressableTypedElement ...$parameters
     * Template parameters for the issue's error message.
     * If these are objects, they should define __toString()
     *
     * @return void
     */
    public static function maybeEmit(
        CodeBase $code_base,
        Context $context,
        string $issue_type,
        int $lineno,
        ...$parameters
    ) {
        self::maybeEmitWithParameters(
            $code_base,
            $context,
            $issue_type,
            $lineno,
            $parameters
        );
    }

    /**
     * @param CodeBase $code_base
     * The code base within which we're operating
     *
     * @param Context $context
     * The context in which the node we're going to be looking
     * at exits.
     *
     * @param string $issue_type
     * The type of issue to emit such as Issue::ParentlessClass
     *
     * @param int $lineno
     * The line number where the issue was found
     *
     * @param array<int,string|int|float|bool|Type|UnionType|FQSEN|TypedElement|UnaddressableTypedElement> $parameters
     * @param ?Suggestion $suggestion (optional)
     *
     * Template parameters for the issue's error message
     *
     * @return void
     */
    public static function maybeEmitWithParameters(
        CodeBase $code_base,
        Context $context,
        string $issue_type,
        int $lineno,
        array $parameters,
        Suggestion $suggestion = null
    ) {
        if (self::shouldSuppressIssue(
            $code_base,
            $context,
            $issue_type,
            $lineno,
            $parameters,
            $suggestion
        )) {
            return;
        }

        Issue::emitWithParameters(
            $issue_type,
            $context->getFile(),
            $lineno,
            $parameters,
            $suggestion
        );
    }

    private static function shouldSuppressIssue(
        CodeBase $code_base,
        Context $context,
        string $issue_type,
        int $lineno,
        array $parameters,
        Suggestion $suggestion = null
    ) : bool {
        if (Config::getValue('disable_suppression')) {
            return false;
        }
        // If this issue type has been suppressed in
        // the config, ignore it
        if (in_array($issue_type, Config::getValue('suppress_issue_types') ?? [])) {
            return true;
        }
        // If a white-list of allowed issue types is defined,
        // only emit issues on the white-list
        $whitelist_issue_types = Config::getValue('whitelist_issue_types') ?? [];
        if (count($whitelist_issue_types) > 0 &&
            !in_array($issue_type, $whitelist_issue_types)) {
            return true;
        }

        if ($context->hasSuppressIssue($code_base, $issue_type)) {
            return true;
        }

        return ConfigPluginSet::instance()->shouldSuppressIssue(
            $code_base,
            $context,
            $issue_type,
            $lineno,
            $parameters,
            $suggestion
        );
    }
}
