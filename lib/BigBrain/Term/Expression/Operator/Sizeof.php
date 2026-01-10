<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Type;

class Sizeof implements Expression
{
	use Term\HasToken;

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
		$valueType = $this->value->resultType($env);
		if ($valueType instanceof Type\Pointer)
		{
			return new Type\Computable($valueType->size());
		}
		else
		{
			throw new CompileError('array expected', $this->value->token());
		}
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		throw new CompileError('not expected', $this->value->token());
	}

	public function hasVariable(string $name) : bool
	{
		return $this->value->hasVariable($name);
	}

	public function __toString() : string
	{
		return sprintf('sizeof %s', $this->value);
	}
}