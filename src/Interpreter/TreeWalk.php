<?php

namespace Cinter\Interpreter;

use Cinter\Runtime\Int64;
use Cinter\Runtime\Str;
use Cinter\Runtime\Value;
use Error;
use Exception;
use PHPCParser\Node\Decl;
use PHPCParser\Node\Decl\NamedDecl\ValueDecl\DeclaratorDecl\FunctionDecl;
use PHPCParser\Node\Decl\NamedDecl\ValueDecl\DeclaratorDecl\VarDecl;
use PHPCParser\Node\Stmt;
use PHPCParser\Node\Stmt\DeclStmt;
use PHPCParser\Node\Stmt\ReturnStmt;
use PHPCParser\Node\Stmt\ValueStmt\Expr;
use PHPCParser\Node\Stmt\ValueStmt\Expr\CallExpr;
use PHPCParser\Node\Stmt\ValueStmt\Expr\DeclRefExpr;
use PHPCParser\Node\Stmt\ValueStmt\Expr\IntegerLiteral;
use PHPCParser\Node\Stmt\ValueStmt\Expr\StringLiteral;
use PHPCParser\Node\TranslationUnitDecl;
use Throwable;

use function Cinter\c_printf;
use function Cinter\todo;

final class TreeWalk implements Interpreter
{
    /** @var array<string, FunctionDecl> */
    private array $functions = [];

    /** @var array<string, \Closure> */
    private array $internalFunctions = [];

    /** @var array<array<string, ?Value>> */
    private array $frames = [];

    public function __construct()
    {
        $this->internalFunctions = [
            'printf' => c_printf(...),
        ];
    }

    public function interpret(TranslationUnitDecl $ast): int
    {
        foreach ($ast->declarations as $declaration) {
            $this->interpretDecl($declaration);
        };

        if (! isset($this->functions['main'])) {
            throw new Exception('No main function found.');
        }

        return $this->runMain();
    }

    private function interpretDecl(Decl $decl)
    {
        if ($decl instanceof FunctionDecl) {
            $this->interpretFunctionDecl($decl);
        } elseif ($decl instanceof VarDecl) {
            $this->getCurrentCallFrame()[$decl->name] = $decl->initializer !== null ? $this->interpretExpr($decl->initializer) : null;
        } else {
            throw new Exception('Unrecognised decl: ' . $decl::class);
        }
    }

    private function &getCurrentCallFrame(): array
    {
        return $this->frames[count($this->frames) - 1];
    }

    private function runMain(): int
    {
        $this->frames[] = [];

        $main = $this->functions['main'];
        
        foreach ($main->stmts->stmts as $statement) {
            try {
                $this->interpretStatement($statement);
            } catch (ReturnEscapeHatch $return) {
                if ($return->value === null) {
                    throw new Exception('Cannot return void from main.');
                }

                if (! $return->value instanceof Int64) {
                    throw new Exception("Cannot return {$return->value->inspect()} from main.");
                }

                return $return->value->value;
            }
        }

        return 0;
    }

    private function interpretStatement(Stmt $stmt): void
    {
        if ($stmt instanceof CallExpr) {
            $args = [];

            foreach ($stmt->args as $arg) {
                $args[] = $this->interpretExpr($arg);
            }

            if (array_key_exists($stmt->fn->name, $this->internalFunctions)) {
                $return = call_user_func_array($this->internalFunctions[$stmt->fn->name], $args);
            } else {
                todo();
            }
        } elseif ($stmt instanceof ReturnStmt) {
            $result = $stmt->result !== null ? $this->interpretExpr($stmt->result) : null;

            throw new ReturnEscapeHatch($result);
        } elseif ($stmt instanceof DeclStmt) {
            foreach ($stmt->declarations->declarations as $decl) {
                $this->interpretDecl($decl);
            }
        } else {
            throw new Exception('Unrecognised statement: ' . $stmt::class);
        }
    }

    private function interpretExpr(Expr $expr): Value
    {
        if ($expr instanceof StringLiteral) {
            return new Str($expr->value);
        } elseif ($expr instanceof IntegerLiteral) {
            return new Int64((int) $expr->value);
        } elseif ($expr instanceof DeclRefExpr) {
            if ($var = $this->getCurrentCallFrame()[$expr->name]) {
                return $var;
            } else {
                throw new Exception('Unhandled reference to declaration.');
            }
        } else {
            throw new Exception('Unrecognised expression: ' . $expr::class);
        }
    }

    private function interpretFunctionDecl(FunctionDecl $decl): void
    {
        $this->functions[$decl->name] = $decl;
    }
}

class ReturnEscapeHatch extends Error
{
    public function __construct(
        public readonly ?Value $value,
    ) {}
}