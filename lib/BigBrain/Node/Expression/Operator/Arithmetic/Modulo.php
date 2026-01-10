<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Node\Expression\Calculation;

class Modulo extends Binary
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left % $right;
	}

	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);
		$right = $env->processor()->reserve($left, $result);
		$this->right->compileCalculation($env, $right);

		Calculation\Modulo::divide($env, $left, $right, $result);
		$env->processor()->release($left, $right);
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		if ($constant === 0) { return; }

		$left = $env->processor()->reserve($result);
		$env->processor()->addConstant($left, $constant);
		$right = $env->processor()->reserve($left, $result);
		$this->right->compileCalculation($env, $right);

		Calculation\Modulo::divide($env, $left, $right, $result);
		$env->processor()->release($left, $right);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		if ($constant === 0) { throw new CompileError('division by zero', $this->token()); }
		if ($constant === 1) { return; }

		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);

		Calculation\Modulo::divideByConstant($env, $left, $constant, $result);
		$env->processor()->release($left);
	}

	public function __toString() : string
	{
		return sprintf('(%s %% %s)', $this->left, $this->right);
	}
}