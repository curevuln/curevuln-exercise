<?php declare(strict_types=1);

namespace Phan\Language\Type;

use Phan\Language\Type;

/**
 * Phan's representation for annotations such as `Closure(MyClass):MyOtherClass`
 * @see ClosureType for the representation of `Closure` (and closures for function-like FQSENs)
 */
final class ClosureDeclarationType extends FunctionLikeDeclarationType
{
    /** @override */
    const NAME = 'Closure';

    /**
     * @return bool
     * True if this Type can be cast to the given Type
     * cleanly
     */
    public function canCastToNonNullableType(Type $type) : bool
    {
        if ($type->isCallable()) {
            if ($type instanceof FunctionLikeDeclarationType) {
                return $this->canCastToNonNullableFunctionLikeDeclarationType($type);
            }
            return true;
        }

        return parent::canCastToNonNullableType($type);
    }
}
