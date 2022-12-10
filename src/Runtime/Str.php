<?php

namespace Cinter\Runtime;

class Str implements Value
{
    public function __construct(
        public string $value,
    ) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function inspect(): string
    {
        return 'char *';
    }
}