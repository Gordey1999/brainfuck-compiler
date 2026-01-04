<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\MemoryCellTyped;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class ScalarVariable implements Expression, Assignable
{
	use BigBrain\Term\HasLexeme;

	public function __construct(Lexeme $name)
	{
		$this->lexeme = $name;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		// do nothing
	}

	public function name() : Lexeme
	{
		return $this->lexeme;
	}

	public function memoryCell(Environment $env) : MemoryCellTyped
	{
		return $env->memory()->get($this->name());
	}

	public function resultType(Environment $env) : Type\Type
	{
		return $this->memoryCell($env)->type();
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$cell = $this->memoryCell($env);
		$env->processor()->copyNumber($cell, $result);
	}

	public function hasVariable(string $name) : bool
	{
		return $this->name()->value() === $name;
	}

	public function assign(Environment $env, Expression $value, string $modifier) : void
	{
		$result = $value->resultType($env);

		if ($result instanceof Type\Computable)
		{
			$this->assignComputed($env, $result, $value, $modifier);
		}
		else if ($result instanceof Type\Scalar)
		{
			$this->assignVariable($env, $result, $value, $modifier);
		}
		else
		{
			throw new CompileError('scalar value expected', $value->lexeme());
		}
	}

	protected function assignComputed(Environment $env, Type\Computable $result, Expression $value, string $modifier) : void
	{
		$memoryCell = $this->memoryCell($env);

		if (!$result->numericCompatible())
		{
			throw new CompileError('numeric type expected', $value->lexeme());
		}

		if ($modifier === self::ASSIGN_SET)
		{
			$env->processor()->unset($memoryCell);
		}

		if ($memoryCell->type() instanceof Type\Boolean)
		{
			$env->processor()->addConstant($memoryCell, $result->getNumeric() !== 0);
		}
		else
		{
			if ($modifier === self::ASSIGN_SUB)
			{
				$env->processor()->subConstant($memoryCell, $result->getNumeric());
			}
			else
			{
				$env->processor()->addConstant($memoryCell, $result->getNumeric());
			}
		}
	}

	protected function assignVariable(Environment $env, Type\Scalar $result, Expression $value, string $modifier) : void
	{
		$memoryCell = $this->memoryCell($env);

		$boolCastingNeed = $this->resultType($env) instanceof Type\Boolean
			&& !$result instanceof Type\Boolean;

		if ($boolCastingNeed || $value->hasVariable($memoryCell->label()) || $modifier === self::ASSIGN_SUB)
		{
			$tempResult = $env->processor()->reserve($memoryCell);
			$value->compileCalculation($env, $tempResult);

			if ($modifier === self::ASSIGN_SET)
			{
				$env->processor()->unset($memoryCell);
			}

			if ($boolCastingNeed)
			{
				if ($modifier === self::ASSIGN_SET)
				{
					$env->processor()->moveBoolean($tempResult, $memoryCell);
				}
				else if ($modifier === self::ASSIGN_ADD)
				{
					// todo
				}
			}
			else
			{
				if ($modifier === self::ASSIGN_SUB)
				{
					$env->processor()->sub($memoryCell, $tempResult);
				}
				else
				{
					$env->processor()->moveNumber($tempResult, $memoryCell);
				}
			}
			$env->processor()->release($tempResult);
		}
		else
		{
			if ($modifier === self::ASSIGN_SET)
			{
				$env->processor()->unset($memoryCell);
			}

			$value->compileCalculation($env, $memoryCell);
		}
	}

	public function __toString() : string
	{
		return $this->lexeme()->value();
	}
}