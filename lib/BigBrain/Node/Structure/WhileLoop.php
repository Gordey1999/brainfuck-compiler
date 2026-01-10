<?php

namespace Gordy\Brainfuck\BigBrain\Node\Structure;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Node;
use Gordy\Brainfuck\BigBrain\Type;

class WhileLoop implements Node\Structure
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
		$env->stack()->newScope();

		$exprType = $this->expression->resultType($env);


		if ($this->expression instanceof Node\Expression\ScalarVariable)
		{
			$env->stream()->blockComment($this);

			$cell = $this->expression->memoryCell($env);

			$env->processor()->while($cell, function() use ($env) {
				$this->scope->compile($env);
			}, "while $cell > 0");
		}
		else if ($exprType instanceof Type\Computable && $exprType->numericCompatible())
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
			$env->stream()->blockComment($this);

			$condition = $env->processor()->reserve();
			$this->expression->compileCalculation($env, $condition);
			$env->processor()->while($condition, function() use ($env, $condition) {
				$this->scope->compile($env);
				$env->stream()->blockComment('recalculate condition');
				$env->processor()->unset($condition);
				$this->expression->compileCalculation($env, $condition);
			}, "while $condition");

			$env->processor()->release($condition);
		}
		else
		{
			throw new CompileError('scalar condition expected', $this->expression->token());
		}
		$env->stack()->dropScope();
	}

	public function __toString() : string
	{
		$expr = $this->expression;
		return "while ($expr)";
	}
}