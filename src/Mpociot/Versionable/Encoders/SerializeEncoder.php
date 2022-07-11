<?php

namespace Mpociot\Versionable\Encoders;

class SerializeEncoder implements Encoder
{
    public function encode($data): string
    {
        return serialize($data);
    }

    public function decode(string $data)
    {
        return unserialize($data);
    }
}
