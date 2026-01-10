<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Node\Expression;
use Gordy\Brainfuck\BigBrain\Node;
use Gordy\Brainfuck\BigBrain\Type;

class Not implements Expression
{
	use Node\HasToken;

	protected Expression $value;

	public function __construct(Expression $value, Token $token)
	{
		$this->value = $value;
		$this->token = $token;
	}

	public function compile(Environment $env) : void
	{
		$this->value->compile($env);
	}

	public function resultType(Environment $env) : Type\Type
	{
		return new Type\Boolean();
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$valueType = $this->value->resultType($env);
		if ($valueType instanceof Type\Computable && $valueType->numericCompatible())
		{
			$value = $valueType->getNumeric();
			if ($value === 0)
			{
				$env->processor()->addConstant($result, 1);
			}
		}
		else if ($valueType instanceof Type\Boolean)
		{
			$temp = $env->processor()->reserve($result);
			$this->value->compileCalculation($env, $temp);
			$env->processor()->not($temp);
			$env->processor()->moveBoolean($temp, $result);
			$env->processor()->release($temp);
		}
		else if ($valueType instanceof Type\Scalar)
		{
			$this->value->compileCalculation($env, $result);
			$temp = $env->processor()->reserve($result);
			$env->processor()->moveBoolean($result, $temp);
			$env->processor()->not($temp);
			$env->processor()->moveBoolean($temp, $result);
			$env->processor()->release($temp);
		}
		else
		{
			throw new CompileError('scalar value expected', $this->value->token());
		}
	}

	public function hasVariable(string $name) : bool
	{
		return $this->value->hasVariable($name);
	}

	public function __toString() : string
	{
		return sprintf('!%s', $this->value);
	}
}