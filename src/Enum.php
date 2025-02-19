<?php

declare(strict_types=1);

/**
 * Contains the Enum Class.
 *
 * @copyright   Copyright (c) 2013-2022 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2013-09-23
 */

namespace Konekt\Enum;

/**
 * Abstract class that enables creation of PHP enums.
 *
 * All you have to do is extend this class and define some constants.
 */
abstract class Enum
{
    /** Constant with default value for creating enum object */
    public const __DEFAULT = null;

    protected mixed $value;

    protected static bool $unknownValuesFallbackToDefault = false;

    private static array $meta = [];

    /**
     * Class constructor.
     *
     * @param mixed $value Any defined value
     *
     * @throws \UnexpectedValueException If value is not valid enum value
     */
    public function __construct(mixed $value = null)
    {
        self::bootClass();

        if ($value === null && static::hasNot(null)) {
            $value = static::__DEFAULT;
        }

        if (!static::has($value)) {
            if (static::$unknownValuesFallbackToDefault) {
                $value = static::__DEFAULT;
            } else {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Given value (%s %s) is not in enum `%s`',
                        gettype($value),
                        is_string($value) ? '"' . $value . '"' : $value,
                        static::class
                    )
                );
            }
        }

        //trick below is needed to make sure the value of original type gets set
        $this->value = static::values()[array_search($value, static::values(), true)];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->label();
    }

    /**
     * Magic property getter. If the property is in format `is_const_name` it checks
     * if the enum instance equals to the value of the constant of the const_name
     * from the property name. If $name doesn't match, a PHP notice is emitted
     *
     * @param $name
     *
     * @return bool
     */
    public function __get($name)
    {
        if (str_starts_with($name, 'is_') && strlen($name) > 3) {
            $constName = self::strToConstName(substr($name, 3));
            if (self::hasConst($constName)) {
                return $this->equalsByConstName($constName);
            }
        }

        trigger_error('Undefined property: ' . static::class . '::' . $name);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool
     */
    public function __call(string $name, array $arguments)
    {
        if (str_starts_with($name, 'is') && strlen($name) > 2 && ctype_upper($name[2])) {
            $constName = self::strToConstName(substr($name, 2));
            if (self::hasConst($constName)) {
                return $this->equalsByConstName($constName);
            }
        }

        trigger_error(
            sprintf('Call to undefined method: %s::%s()', static::class, $name),
            E_USER_WARNING
        );
    }

    /**
     * Magic constructor to be used like: FancyEnum::SHINY_VALUE() where the method name is a const of the class.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @throws \BadMethodCallException
     *
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        if (self::hasConst($name)) {
            return new static(constant(static::class . '::' . $name));
        }

        throw new \BadMethodCallException(
            sprintf(
                'No such value (`%s`) or static method in this class %s',
                $name,
                static::class
            )
        );
    }

    /**
     * Returns the value of the enum instance.
     *
     * @return mixed
     */
    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * Returns the label (string to be displayed on UI) of an instance.
     * It returns the value if no label was set.
     *
     * @return string
     */
    public function label(): string
    {
        return static::getLabel($this->value);
    }

    /**
     * Checks if two enums are equal. Value and class are both matched.
     * Value check is not type strict.
     *
     * @param mixed $object
     *
     * @return bool True if enums are equal
     */
    public function equals(object $object): bool
    {
        if (!($object instanceof self) || !self::compatibles(get_class($object), static::class)) {
            return false;
        }

        return $this->value() === $object->value();
    }

    /**
     * Checks if two enums are NOT equal. Value and class are both matched.
     * Value check is not type strict.
     *
     * @param mixed $object
     *
     * @return bool True if enums do not equal
     */
    public function notEquals(object $object): bool
    {
        return !$this->equals($object);
    }

    /**
     * Returns the default value of the class. Equals to the __DEFAULT constant.
     */
    public static function defaultValue(): mixed
    {
        return static::__DEFAULT;
    }

    /**
     * Factory method for creating instance.
     *
     * @param mixed|null $value The value for the instance
     *
     * @return static
     */
    public static function create($value = null): static
    {
        return new static($value);
    }

    /**
     * Returns whether a const is present in the specific enum class.
     *
     * @param string $const
     *
     * @return bool
     */
    public static function hasConst(string $const): bool
    {
        return in_array($const, static::consts(), true);
    }

    /**
     * Returns the consts (except for __DEFAULT) of the class.
     *
     * @return array
     */
    public static function consts(): array
    {
        self::bootClass();

        return array_keys(self::$meta[static::class]);
    }

    public static function has(mixed $value): bool
    {
        return in_array($value, static::values(), true);
    }

    public static function hasNot(mixed $value): bool
    {
        return !static::has($value);
    }

    /**
     * Returns the array of values.
     *
     * @return array
     */
    public static function values(): array
    {
        self::bootClass();

        return array_values(self::$meta[static::class]);
    }

    /**
     * Returns the array of labels.
     *
     * @return array
     */
    public static function labels(): array
    {
        self::bootClass();

        $result = [];

        foreach (static::values() as $value) {
            $result[] = static::getLabel($value);
        }

        return $result;
    }

    /**
     * Returns an array of value => label pairs.
     * Ready to pass to dropdowns.
     *
     * Example:
     *      ```
     *          const FOO = 'foo';
     *          const BAR = 'bar'
     *
     *          protected static $labels = [
     *              self::FOO => 'I am foo',
     *              self::BAR => 'I am bar'
     *          ];
     *      ```
     *      self::choices returns:
     *      ```
     *          [
     *              'foo' => 'I am foo',
     *              'bar' => 'I am bar'
     *          ]
     *      ```
     *
     * @return array
     */
    public static function choices(): array
    {
        self::bootClass();

        $result = [];

        foreach (static::values() as $value) {
            $result[$value] = static::getLabel($value);
        }

        return $result;
    }

    /**
     * Returns an associative array with const names as key and their corresponding values as value.
     *
     * @return array
     */
    public static function toArray(): array
    {
        self::bootClass();

        return self::$meta[static::class];
    }

    /**
     * Clears static class metadata. Mainly useful in testing environments.
     * Next time the enum class gets used, the class will be rebooted.
     */
    public static function reset(): void
    {
        if (array_key_exists(static::class, self::$meta)) {
            unset(self::$meta[static::class]);
        }
    }

    /**
     * Returns whether the enum instance equals with a value of the same
     * type created from the given const name
     *
     * @param string $const
     *
     * @return bool
     */
    private function equalsByConstName(string $const): bool
    {
        return $this->equals(
            static::create(
                constant(static::class . '::' . $const)
            )
        );
    }

    /**
     * Initializes the constants array for the class if necessary.
     */
    private static function bootClass(): void
    {
        if (!array_key_exists(static::class, self::$meta)) {
            self::$meta[static::class] = (new \ReflectionClass(static::class))->getConstants();
            unset(self::$meta[static::class]['__DEFAULT']);

            if (method_exists(static::class, 'boot')) {
                static::boot();
            }
        }
    }

    /**
     * Returns whether two enum classes are compatible (are the same type or one descends from the other).
     *
     * @param string $class1
     * @param string $class2
     *
     * @return bool
     */
    private static function compatibles(string $class1, string $class2): bool
    {
        return $class1 === $class2 || is_subclass_of($class1, $class2) || is_subclass_of($class2, $class1);
    }

    /**
     * Returns whether the labels property is defined on the actual class.
     *
     * @return bool
     */
    private static function hasLabels(): bool
    {
        return property_exists(static::class, 'labels');
    }

    /**
     * Returns the label for a given value.
     *
     * !!Make sure it only gets called after bootClass()!!
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function getLabel(mixed $value): string
    {
        self::bootClass();

        if (static::hasLabels() && isset(static::$labels[$value])) {
            return (string) static::$labels[$value];
        }

        return (string) $value;
    }

    private static function strToConstName($str): string
    {
        if (! ctype_lower($str)) {
            $str = preg_replace('/\s+/u', '', ucwords($str));
            $str = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . '_', $str));
            $str = preg_replace('/(.+?)([0-9]+)/u', '$1' . '_' . '$2', $str);
        }

        return strtoupper(str_replace('__', '_', $str));
    }
}
