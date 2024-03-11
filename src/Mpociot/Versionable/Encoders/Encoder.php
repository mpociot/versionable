<?php

namespace Mpociot\Versionable\Encoders;

interface Encoder
{
    /**
     * @param mixed $data
     */
    public function encode($data): string;

    /**
     * @return mixed
     */
    public function decode(string $data);
}
