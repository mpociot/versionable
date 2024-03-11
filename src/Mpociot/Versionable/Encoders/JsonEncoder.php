<?php

namespace Mpociot\Versionable\Encoders;

class JsonEncoder implements Encoder
{
    public function encode($data): string
    {
        return json_encode($data);
    }

    public function decode(string $data)
    {
        return json_decode($data, true);
    }
}
