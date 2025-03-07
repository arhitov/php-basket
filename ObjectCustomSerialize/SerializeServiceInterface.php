<?php

namespace App\Contracts;

use ValueError;

interface SerializeServiceInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function serialize(mixed $data): string;

    /**
     * @param string $json
     * @return mixed
     * @throws ValueError
     */
    public function unserialize(string $json): mixed;
}
