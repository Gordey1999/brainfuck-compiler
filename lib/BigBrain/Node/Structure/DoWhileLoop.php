<?php

namespace Gordy\Brainfuck\BigBrain\Node\Structure;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Node;
use Gordy\Brainfuck\BigBrain\Type;

class DoWhileLoop implements Node\Structure
{
	use Node\HasToken;

	protected Node\Expression $condition;
	protected Node\Scope $body;

	public function __construct(Node\Expression $condition, Node\Scope $body, Token $token)
	{
		$this->condition = $condition;
		$this->body = $body;
		$this->token = $token;
	}

	public function compile(Environment $env) : void
	{
		$exprType = $this->condition->resultType($env);

		if ($exprType instanceof Type\Computable && $exprType->numericCompatible())
		{
			if ($exprType->getNumeric() === 0)
			{
				// do nothing
			}
			else
			{
				throw new CompileError('infinite loop detected', $this->condition->token());
			}
		}
		else if ($exprType instanceof Type\Scalar)
		{
			$env->stream()->blockComment('do');

			$condition = $env->processor()->reserve();
			$env->processor()->addConstant($condition, 1);

			$env->processor()->while($condition, function() use ($env, $condition) {
				$this->body->compile($env);
				$env->stream()->blockComment($this);
				$env->processor()->unset($condition);
				$this->condition->compileCalculation($env, $condition);
			}, "while $condition");

			$env->processor()->release($condition);
		}
		else
		{
			throw new CompileError('scalar condition expected', $this->condition->token());
		}
	}

	public function __toString() : string
	{
		$expr = $this->condition;
		return "while ($expr)";
	}
}