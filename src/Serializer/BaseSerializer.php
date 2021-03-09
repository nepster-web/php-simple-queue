<?php

declare(strict_types=1);

namespace Simple\Queue\Serializer;

/**
 * Class BaseSerializer
 * @package Simple\Queue\Serializer
 */
class BaseSerializer implements SerializerInterface
{
    /**
     * @inheritDoc
     */
    public function serialize($data): string
    {
        return serialize($data);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(string $data)
    {
        return unserialize($data);
    }
}
