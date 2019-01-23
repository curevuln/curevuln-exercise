<?php declare(strict_types=1);

namespace Phan\Language\Element;

use Phan\Language\Context;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\FQSEN\FullyQualifiedMethodName;
use Phan\Language\Type;
use Phan\Language\Type\NullType;
use Phan\Language\UnionType;

/**
 * This returns internal function declarations for a given function/method FQSEN,
 * using Reflection and/or Phan's internal function signature map.
 */
class FunctionFactory
{
    /**
     * @return array<int,Func>
     * One or more (alternate) functions begotten from
     * reflection info and internal functions data
     */
    public static function functionListFromReflectionFunction(
        FullyQualifiedFunctionName $fqsen,
        \ReflectionFunction $reflection_function
    ) : array {

        $context = new Context();

        $function = new Func(
            $context,
            $fqsen->getNamespacedName(),
            UnionType::empty(),
            0,
            $fqsen,
            null
        );

        $function->setNumberOfRequiredParameters(
            $reflection_function->getNumberOfRequiredParameters()
        );

        $function->setNumberOfOptionalParameters(
            $reflection_function->getNumberOfParameters()
            - $reflection_function->getNumberOfRequiredParameters()
        );
        $function->setIsDeprecated($reflection_function->isDeprecated());
        $function->setRealReturnType(UnionType::fromReflectionType($reflection_function->getReturnType()));
        $function->setRealParameterList(Parameter::listFromReflectionParameterList($reflection_function->getParameters()));

        return self::functionListFromFunction($function);
    }

    /**
     * @param string[] $signature
     * @return array<int,Func>
     * One or more (alternate) methods begotten from
     * reflection info and internal method data
     */
    public static function functionListFromSignature(
        FullyQualifiedFunctionName $fqsen,
        array $signature
    ) : array {

        $context = new Context();

        $return_type = UnionType::fromStringInContext(
            $signature[0],
            $context,
            Type::FROM_TYPE
        );
        unset($signature[0]);

        $func = new Func(
            $context,
            $fqsen->getNamespacedName(),
            $return_type,
            0,
            $fqsen,
            []  // will be filled in by functionListFromFunction
        );

        return self::functionListFromFunction($func);
    }

    /**
     * @return array<int,Method> a list of 1 or more method signatures from a ReflectionMethod
     * and Phan's alternate signatures for that method's FQSEN in FunctionSignatureMap.
     */
    public static function methodListFromReflectionClassAndMethod(
        Context $context,
        \ReflectionClass $class,
        \ReflectionMethod $reflection_method
    ) : array {
        $class_name = $class->getName();
        $method_fqsen = FullyQualifiedMethodName::make(
            // @phan-suppress-next-line PhanThrowTypeAbsentForCall
            FullyQualifiedClassName::fromFullyQualifiedString($class_name),
            $reflection_method->getName()
        );


        $method = new Method(
            $context,
            $reflection_method->name,
            UnionType::empty(),
            $reflection_method->getModifiers(),
            $method_fqsen,
            null
        );

        $method->setNumberOfRequiredParameters(
            $reflection_method->getNumberOfRequiredParameters()
        );

        $method->setNumberOfOptionalParameters(
            $reflection_method->getNumberOfParameters()
            - $reflection_method->getNumberOfRequiredParameters()
        );

        if ($method->getIsMagicCall() || $method->getIsMagicCallStatic()) {
            $method->setNumberOfOptionalParameters(FunctionInterface::INFINITE_PARAMETERS);
            $method->setNumberOfRequiredParameters(0);
        }
        $method->setIsDeprecated($reflection_method->isDeprecated());
        // https://github.com/phan/phan/issues/888 - Reflection for that class's parameters causes php to throw/hang
        if ($class_name !== 'ServerResponse') {
            $method->setRealReturnType(UnionType::fromReflectionType($reflection_method->getReturnType()));
            $method->setRealParameterList(Parameter::listFromReflectionParameterList($reflection_method->getParameters()));
        }

        return self::functionListFromFunction($method);
    }

    /**
     * @param FunctionInterface $function
     * Get a list of methods hydrated with type information
     * for the given partial method
     *
     * @return array<int,FunctionInterface>
     * A list of typed functions/methods based on the given method
     */
    public static function functionListFromFunction(
        FunctionInterface $function
    ) : array {
        // See if we have any type information for this
        // internal function
        $map_list = UnionType::internalFunctionSignatureMapForFQSEN(
            $function->getFQSEN()
        );

        if (!$map_list) {
            return [$function];
        }

        $alternate_id = 0;
        /**
         * @param array<string,mixed> $map
         * @suppress PhanPossiblyFalseTypeArgumentInternal, PhanPossiblyFalseTypeArgument
         */
        return \array_map(function ($map) use (
            $function,
            &$alternate_id
        ) : FunctionInterface {
            $alternate_function = clone($function);

            $alternate_function->setFQSEN(
                $alternate_function->getFQSEN()->withAlternateId(
                    $alternate_id++
                )
            );

            // Set the return type if one is defined
            if (isset($map['return_type'])) {
                $alternate_function->setUnionType($map['return_type']);
            }
            $alternate_function->clearParameterList();

            // Load parameter types if defined
            foreach ($map['parameter_name_type_map'] ?? [] as $parameter_name => $parameter_type) {
                $flags = 0;
                $phan_flags = 0;
                $is_optional = false;

                // Check to see if its a pass-by-reference parameter
                if (($parameter_name[0] ?? '') === '&') {
                    $flags |= \ast\flags\PARAM_REF;
                    $parameter_name = \substr($parameter_name, 1);
                    if (\strncmp($parameter_name, 'rw_', 3) === 0) {
                        $phan_flags |= Flags::IS_READ_REFERENCE | Flags::IS_WRITE_REFERENCE;
                        $parameter_name = \substr($parameter_name, 3);
                    } elseif (\strncmp($parameter_name, 'w_', 2) === 0) {
                        $phan_flags |= Flags::IS_WRITE_REFERENCE;
                        $parameter_name = \substr($parameter_name, 2);
                    }
                }

                // Check to see if its variadic
                if (\strpos($parameter_name, '...') !== false) {
                    $flags |= \ast\flags\PARAM_VARIADIC;
                    $parameter_name = \str_replace('...', '', $parameter_name);
                }

                // Check to see if its an optional parameter
                if (\strpos($parameter_name, '=') !== false) {
                    $is_optional = true;
                    $parameter_name = \str_replace('=', '', $parameter_name);
                }

                $parameter = Parameter::create(
                    $function->getContext(),
                    $parameter_name,
                    $parameter_type,
                    $flags
                );
                $parameter->enablePhanFlagBits($phan_flags);

                if ($is_optional) {
                    // TODO: could check isDefaultValueAvailable and getDefaultValue, for a better idea.
                    // I don't see any cases where this will be used for internal types, though.
                    $parameter->setDefaultValueType(
                        NullType::instance(false)->asUnionType()
                    );
                }

                // Add the parameter
                $alternate_function->appendParameter($parameter);
            }

            // TODO: Store the "real" number of required parameters,
            // if this is out of sync with the extension's ReflectionMethod->getParameterList()?
            // (e.g. third party extensions may add more required parameters?)
            $alternate_function->setNumberOfRequiredParameters(
                \array_reduce(
                    $alternate_function->getParameterList(),
                    function (int $carry, Parameter $parameter) : int {
                        return ($carry + (
                            $parameter->isOptional() ? 0 : 1
                        ));
                    },
                    0
                )
            );

            $alternate_function->setNumberOfOptionalParameters(
                \count($alternate_function->getParameterList()) -
                $alternate_function->getNumberOfRequiredParameters()
            );

            if ($alternate_function instanceof Method) {
                if ($alternate_function->getIsMagicCall() || $alternate_function->getIsMagicCallStatic()) {
                    $alternate_function->setNumberOfOptionalParameters(999);
                    $alternate_function->setNumberOfRequiredParameters(0);
                }
            }

            return $alternate_function;
        }, $map_list);
    }
}
