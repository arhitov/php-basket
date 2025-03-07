<?php

namespace App\Services;

use App\Contracts\SerializeServiceInterface;
use ValueError;

class SerializeSimpleService implements SerializeServiceInterface
{
    /** @inheritDoc */
    public function serialize(mixed $data): string
    {
        return json_encode(serialize($data),JSON_UNESCAPED_UNICODE);
    }

    /** @inheritDoc */
    public function unserialize(string $json): mixed
    {
        return json_validate($json)
            ? unserialize(json_decode($json))
            : throw new ValueError('Строка должна быть в формате JSON');
    }
}
