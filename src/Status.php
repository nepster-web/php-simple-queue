<?php

declare(strict_types=1);

namespace Simple\Queue;

use function in_array;
use InvalidArgumentException;

/**
 * Class Status
 * @package Simple\Queue
 */
class Status
{
    /**
     * Set for a new message
     */
    public const NEW = 'NEW';

    /**
     * Set for a message that is being processed
     */
    public const IN_PROCESS = 'IN_PROCESS';

    /**
     * Set for a message for which an error occurred
     */
    public const FAILURE = 'FAILURE';

    /**
     * Set for a message to be redelivered to the queue
     */
    public const REDELIVERED = 'REDELIVERED';

    /**
     * Set for a message if there is no processor or job
     */
    public const UNDEFINED_HANDLER = 'UNDEFINED_HANDLER';

    /** @var string */
    private string $status;

    /**
     * Status constructor.
     * @param string $value
     */
    public function __construct(string $value)
    {
        if (in_array($value, self::getStatuses(), true) === false) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid message status.', $value));
        }

        $this->status = $value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::NEW,
            self::IN_PROCESS,
            self::FAILURE,
            self::REDELIVERED,
            self::UNDEFINED_HANDLER,
        ];
    }
}
