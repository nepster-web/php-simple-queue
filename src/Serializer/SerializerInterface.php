<?php

declare(strict_types=1);

namespace Simple\Queue\Serializer;

/**
 * Interface SerializerInterface
 * @package Simple\Queue\Serializer
 */
interface SerializerInterface
{
    /**
     * @param $data
     * @return string
     */
    public function serialize($data): string;

    /**
     * @param string $data
     * @return mixed
     */
    public function deserialize(string $data);
}
