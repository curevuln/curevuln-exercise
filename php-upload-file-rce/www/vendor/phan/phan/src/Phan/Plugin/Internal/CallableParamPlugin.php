<?php declare(strict_types=1);

namespace Phan\Plugin\Internal;

use Closure;
use Phan\AST\UnionTypeVisitor;
use Phan\CodeBase;
use Phan\Config;
use Phan\Language\Context;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Type;
use Phan\Language\Type\CallableInterface;
use Phan\Language\Type\ClassStringType;
use Phan\Plugin\ConfigPluginSet;
use Phan\PluginV2;
use Phan\PluginV2\AnalyzeFunctionCallCapability;

/**
 * NOTE: This is automatically loaded by phan. Do not include it in a config.
 *
 * TODO: Analyze returning callables (function() : callable) for any callables that are returned as literals?
 * This would be difficult.
 */
final class CallableParamPlugin extends PluginV2 implements
    AnalyzeFunctionCallCapability
{

    /**
     * @param array<int,int> $callable_params
     * @param array<int,int> $class_params
     * @phan-return Closure(CodeBase,Context,FunctionInterface,array):void
     */
    private static function generateClosure(array $callable_params, array $class_params) : Closure
    {
        $key = \json_encode([$callable_params, $class_params]);
        static $cache = [];
        $closure = $cache[$key] ?? null;
        if ($closure !== null) {
            return $closure;
        }
        $closure = function (CodeBase $code_base, Context $context, FunctionInterface $function, array $args) use ($callable_params, $class_params) {
            // TODO: Implement support for variadic callable arguments.
            foreach ($callable_params as $i) {
                $arg = $args[$i] ?? null;
                if ($arg === null) {
                    continue;
                }

                // Fetch possible functions. As an intentional side effect, this warns about invalid callables.
                // TODO: Check if the signature allows non-array callables? Not sure of desired semantics.
                $function_like_list = UnionTypeVisitor::functionLikeListFromNodeAndContext($code_base, $context, $arg, true);
                if (\count($function_like_list) === 0) {
                    // Nothing to do
                    continue;
                }

                if (Config::get_track_references()) {
                    foreach ($function_like_list as $function) {
                        $function->addReference($context);
                    }
                }
                // self::analyzeFunctionAndNormalArgumentList($code_base, $context, $function_like_list, $arguments);
            }
            foreach ($class_params as $i) {
                $arg = $args[$i] ?? null;
                if ($arg === null) {
                    continue;
                }

                // Fetch possible classes. As an intentional side effect, this warns about invalid/undefined class names.
                $class_list = UnionTypeVisitor::classListFromClassNameNode($code_base, $context, $arg);
                if (\count($class_list) === 0) {
                    // Nothing to do
                    continue;
                }

                if (Config::get_track_references()) {
                    foreach ($class_list as $class) {
                        $class->addReference($context);
                    }
                }
            }
        };

        $cache[$key] = $closure;
        return $closure;
    }

    /**
     * @return array<string,\Closure>
     * @phan-return array<string,Closure(CodeBase,Context,FunctionInterface,array):void>
     */
    private function getAnalyzeFunctionCallClosuresStatic(CodeBase $code_base) : array
    {
        $result = [];
        $add_callable_checker_closure = function (FunctionInterface $function) use (&$result) {
            $callable_params = [];
            $class_params = [];
            foreach ($function->getParameterList() as $i => $param) {
                // If there's a type such as Closure|string|int, don't automatically assume that any string or array passed in is meant to be a callable.
                // Explicitly require at least one type to be `callable`
                if ($param->getUnionType()->hasTypeMatchingCallback(function (Type $type) : bool {
                    // TODO: More specific closure for CallableDeclarationType
                    return $type instanceof CallableInterface;
                })) {
                    $callable_params[] = $i;
                }
                if ($param->getUnionType()->hasTypeMatchingCallback(function (Type $type) : bool {
                    return $type instanceof ClassStringType;
                })) {
                    $class_params[] = $i;
                }
            }
            if (\count($callable_params) === 0 && \count($class_params) === 0) {
                return;
            }
            // Generate a de-duplicated closure.
            // fqsen can be global_function or ClassName::method
            $result[$function->getFQSEN()->__toString()] = self::generateClosure($callable_params, $class_params);
        };

        $add_another_closure = function (string $fqsen, Closure $closure) use (&$result) {
            $result[$fqsen] = ConfigPluginSet::mergeAnalyzeFunctionCallClosures(
                $closure,
                $result[$fqsen] ?? null
            );
        };

        $add_misc_closures = function (FunctionInterface $function) use ($add_callable_checker_closure, $add_another_closure, $code_base) {
            $add_callable_checker_closure($function);
            // @phan-suppress-next-line PhanAccessMethodInternal
            $closure = $function->getCommentParamAssertionClosure($code_base);
            if ($closure) {
                $add_another_closure($function->getFQSEN()->__toString(), $closure);
            }
        };

        foreach ($code_base->getFunctionMap() as $function) {
            $add_misc_closures($function);
        }
        foreach ($code_base->getMethodSet() as $function) {
            $add_misc_closures($function);
        }

        // new ReflectionFunction('my_func') is a usage of my_func()
        // See https://github.com/phan/phan/issues/1204 for note on function_exists() (not supported right now)
        $result['\\ReflectionFunction::__construct'] = self::generateClosure([0], []);
        $result['\\ReflectionClass::__construct'] = self::generateClosure([], [0]);

        // When a codebase calls function_exists(string|callable) is to **check** if a function exists,
        // don't emit PhanUndeclaredFunctionInCallable as a side effect.
        unset($result['\\function_exists']);

        // Don't do redundant work extracting function definitions for commonly invoked functions.
        // TODO: Get actual statistics on how frequently used these are
        unset($result['\\call_user_func']);
        unset($result['\\call_user_func_array']);
        unset($result['\\array_map']);
        unset($result['\\array_filter']);
        // End of commonly used functions.

        return $result;
    }

    /**
     * @phan-return array<string,Closure(CodeBase,Context,FunctionInterface,array):void>
     */
    public function getAnalyzeFunctionCallClosures(CodeBase $code_base) : array
    {
        // Unit tests invoke this repeatedly. Cache it.
        static $analyzers = null;
        if ($analyzers === null) {
            $analyzers = self::getAnalyzeFunctionCallClosuresStatic($code_base);
        }
        return $analyzers;
    }
}
