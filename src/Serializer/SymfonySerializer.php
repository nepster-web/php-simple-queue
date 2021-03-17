<?php

declare(strict_types=1);

namespace Simple\Queue\Serializer;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Class SymfonySerializer
 * @package Simple\Queue\Serializer
 */
class SymfonySerializer implements SerializerInterface
{
    /** @var Serializer */
    private Serializer $serializer;

    /**
     * SymfonySerializer constructor.
     */
    public function __construct()
    {
        $this->serializer = new Serializer([], [new JsonEncoder()]);
    }

    /**
     * @inheritDoc
     */
    public function serialize($data): string
    {
        return $this->serializer->encode($data, JsonEncoder::FORMAT);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(string $data)
    {
        return $this->serializer->decode($data, JsonEncoder::FORMAT);
    }
}
