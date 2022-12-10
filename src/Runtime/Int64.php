<?php

namespace Cinter\Runtime;

class Int64 implements Value
{
    public function __construct(
        public int $value,
    ) {}

    public function getValue(): int
    {
        return $this->value;    
    }

    public function inspect(): string
    {
        return 'int';
    }
}