<?php declare(strict_types=1);

namespace Phan\Language\Type;

use Phan\Language\Type;
use Phan\Config;

use RuntimeException;

/**
 * Phan's representation of the type for a specific integer, e.g. `-1`
 */
final class LiteralIntType extends IntType implements LiteralTypeInterface
{
    /** @var int $value */
    private $value;

    protected function __construct(int $value, bool $is_nullable)
    {
        parent::__construct('\\', self::NAME, [], $is_nullable);
        $this->value = $value;
    }

    /**
     * Only exists to prevent accidentally calling this
     * @internal - do not call
     * @deprecated
     */
    public static function instance(bool $unused_is_nullable)
    {
        throw new RuntimeException('Call ' . self::class . '::instanceForValue() instead');
    }

    /**
     * @return LiteralIntType a unique LiteralIntType for $value (and the nullability)
     */
    public static function instanceForValue(int $value, bool $is_nullable)
    {
        if ($is_nullable) {
            static $nullable_cache = [];
            return $nullable_cache[$value] ?? ($nullable_cache[$value] = new self($value, true));
        }
        static $cache = [];
        return $cache[$value] ?? ($cache[$value] = new self($value, false));
    }

    /**
     * Returns the literal int that this type represents
     * (whether or not this type is nullable)
     */
    public function getValue() : int
    {
        return $this->value;
    }

    public function __toString() : string
    {
        if ($this->is_nullable) {
            return '?' . $this->value;
        }
        return (string)$this->value;
    }

    /** @var IntType the non-nullable int type instance. */
    private static $non_nullable_int_type;
    /** @var IntType the nullable int type instance. */
    private static $nullable_int_type;

    /**
     * Called at the bottom of the file to ensure static properties are set for quick access.
     */
    public static function init()
    {
        self::$non_nullable_int_type = IntType::instance(false);
        self::$nullable_int_type = IntType::instance(true);
    }

    public function asNonLiteralType() : Type
    {
        return $this->is_nullable ? self::$nullable_int_type : self::$non_nullable_int_type;
    }

    /**
     * @return Type[]
     * @override
     */
    public function withFlattenedArrayShapeOrLiteralTypeInstances() : array
    {
        return [$this->is_nullable ? self::$nullable_int_type : self::$non_nullable_int_type];
    }

    public function hasArrayShapeOrLiteralTypeInstances() : bool
    {
        return true;
    }

    /** @override */
    public function getIsPossiblyFalsey() : bool
    {
        return !$this->value;
    }

    /** @override */
    public function getIsAlwaysFalsey() : bool
    {
        return !$this->value;
    }

    /** @override */
    public function getIsPossiblyTruthy() : bool
    {
        return (bool)$this->value;
    }

    /** @override */
    public function getIsAlwaysTruthy() : bool
    {
        return (bool)$this->value;
    }

    /**
     * @return bool
     * True if this Type can be cast to the given Type
     * cleanly
     */
    protected function canCastToNonNullableType(Type $type) : bool
    {
        if ($type instanceof ScalarType) {
            switch ($type::NAME) {
                case 'int':
                    if ($type instanceof LiteralIntType) {
                        return $type->getValue() === $this->getValue();
                    }
                    return true;
                case 'string':
                    if ($type instanceof LiteralStringType) {
                        if ($type->getValue() != $this->value) {
                            // Do a loose equality comparison and check if that permits that
                            // E.g. can't cast 5 to 'foo', but can cast 5 to '5' or '5foo' depending on the other rules
                            return false;
                        }
                    }
                    break;
                case 'float':
                    return true;
                case 'true':
                    if (!$this->value) {
                        return false;
                    }
                    break;
                case 'false':
                    if ($this->value) {
                        return false;
                    }
                    break;
                case 'null':
                    // null is also a scalar.
                    if ($this->value && !Config::get_null_casts_as_any_type()) {
                        return false;
                    }
                    break;
            }
        }

        return parent::canCastToNonNullableType($type);
    }

    /**
     * @param bool $is_nullable
     * Set to true if the type should be nullable, else pass
     * false
     *
     * @return Type
     * A new type that is a copy of this type but with the
     * given nullability value.
     */
    public function withIsNullable(bool $is_nullable) : Type
    {
        if ($is_nullable === $this->is_nullable) {
            return $this;
        }

        return self::instanceForValue(
            $this->value,
            $is_nullable
        );
    }

    /**
     * Check if this type can satisfy a comparison (<, <=, >, >=)
     * @param int|string|float|bool|null $scalar
     * @param int $flags (e.g. \ast\flags\BINARY_IS_SMALLER)
     * @internal
     */
    public function canSatisfyComparison($scalar, int $flags) : bool
    {
        return self::performComparison($this->value, $scalar, $flags);
    }
}

LiteralIntType::init();
