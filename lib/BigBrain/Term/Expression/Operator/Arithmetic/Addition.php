<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Term\Expression\Assignable;
use Gordy\Brainfuck\BigBrain\Term\Expression\ScalarVariable;

class Addition extends Binary
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left + $right;
	}

	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		if ($this->left instanceof self || $this->left instanceof Assignable)
		{
			$this->right->compileCalculation($env, $result);
			$this->left->compileCalculation($env, $result);
		}
		else if ($this->right instanceof self || $this->right instanceof Assignable)
		{
			$this->left->compileCalculation($env, $result);
			$this->right->compileCalculation($env, $result);
		}
		else
		{
			$this->left->compileCalculation($env, $result);
			$right = $env->processor()->reserve($result);
			$this->right->compileCalculation($env, $right);

			$env->processor()->add($right, $result);
			$env->processor()->release($right);
		}
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$this->right->compileCalculation($env, $result);

		$env->processor()->addConstant($result, $constant);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$this->left->compileCalculation($env, $result);

		$env->processor()->addConstant($result, $constant);
	}
}