<?php

declare(strict_types=1);

namespace Simple\Queue;

use function in_array;
use InvalidArgumentException;

/**
 * Class Priority
 * @package Simple\Queue
 */
class Priority
{
    public const VERY_LOW = -2;

    public const LOW = -1;

    public const DEFAULT = 0;

    public const HIGH = 1;

    public const VERY_HIGH = 2;

    /** @var int */
    private int $priority;

    /**
     * Priority constructor.
     * @param int $value
     */
    public function __construct(int $value)
    {
        if (in_array($value, self::getPriorities(), true) === false) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid message priority.', $value));
        }

        $this->priority = $value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->priority;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->priority;
    }

    /**
     * @return array
     */
    public static function getPriorities(): array
    {
        return [
            self::VERY_LOW,
            self::LOW,
            self::DEFAULT,
            self::HIGH,
            self::VERY_HIGH,
        ];
    }
}
