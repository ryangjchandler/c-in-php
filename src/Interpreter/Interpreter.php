<?php

namespace Cinter\Interpreter;

use PHPCParser\Node\TranslationUnitDecl;

interface Interpreter
{
    public function interpret(TranslationUnitDecl $ast): int;
}