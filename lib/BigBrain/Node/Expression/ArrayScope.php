<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use \Gordy\Brainfuck\BigBrain\Node\Expression;
use Gordy\Brainfuck\BigBrain\Node\HasToken;
use Gordy\Brainfuck\BigBrain\Type;

class ArrayScope implements Expression
{
	use HasToken;

	protected Expression $expression;

	public function __construct(Expression $expr, Token $token)
	{
		$this->expression = $expr;
		$this->token = $token;
	}

	public function resultType(Environment $env) : Type\Type
	{
		if ($this->expression instanceof Expression\Operator\Comma)
		{
			$list = $this->expression->list();
		}
		else
		{
			$list = [ $this->expression ];
		}

		$result = [];
		foreach ($list as $item)
		{
			$itemResult = $item->resultType($env);
			if (!$itemResult instanceof Type\Computable)
			{
				throw new CompileError('only constant values allowed', $item->token());
			}
			if ($itemResult->arrayCompatible())
			{
				$result[] = $itemResult->getArray();
			}
			else if ($itemResult->numericCompatible())
			{
				$result[] = $itemResult->getNumeric();
			}
			else
			{
				throw new \Exception('not compatible');
			}
		}

		return new Type\Computable($result);
	}

	public function compile(BigBrain\Environment $env) : void
	{
		throw new CompileError('array scope not allowed here', $this->token);
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		throw new \Exception('not implemented');
	}

	public function hasVariable(string $name) : bool
	{
		return false;
	}

	public function __toString() : string
	{
		return sprintf('[%s]', $this->expression);
	}
}