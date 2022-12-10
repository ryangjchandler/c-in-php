<?php

namespace Cinter\Runtime;

interface Value
{
    public function inspect(): string;
    public function getValue(): mixed;
}