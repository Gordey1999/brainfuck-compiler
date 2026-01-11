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

	protected Node\Expression $expression;
	protected Node\Scope $scope;

	public function __construct(Node\Expression $expression, Node\Scope $scope, Token $token)
	{
		$this->expression = $expression;
		$this->scope = $scope;
		$this->token = $token;
	}
	public function compile(Environment $env) : void
	{
		$exprType = $this->expression->resultType($env);

		if ($exprType instanceof Type\Computable && $exprType->numericCompatible())
		{
			if ($exprType->getNumeric() === 0)
			{
				// do nothing
			}
			else
			{
				throw new CompileError('infinite loop detected', $this->expression->token());
			}
		}
		else if ($exprType instanceof Type\Scalar)
		{
			$env->stream()->blockComment('do');

			$condition = $env->processor()->reserve();
			$env->processor()->addConstant($condition, 1);

			$env->processor()->while($condition, function() use ($env, $condition) {
				$this->scope->compile($env);
				$env->stream()->blockComment($this);
				$env->processor()->unset($condition);
				$this->expression->compileCalculation($env, $condition);
			}, "while $condition");

			$env->processor()->release($condition);
		}
		else
		{
			throw new CompileError('scalar condition expected', $this->expression->token());
		}
	}

	public function __toString() : string
	{
		$expr = $this->expression;
		return "while ($expr)";
	}
}