<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Multiplication extends Skeleton
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left * $right;
	}

	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		[$left, $right] = $env->processor()->reserveSeveral(2, $result);

		$this->left->compileCalculation($env, $left);
		$this->right->compileCalculation($env, $right);

		$env->processor()->multiply($left, $right, $result);

		$env->processor()->release($left, $right);
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$right = $env->processor()->reserve($result);
		$this->right->compileCalculation($env, $right);

		$env->processor()->multiplyByConstant($right, $constant, $result);

		$env->processor()->release($right);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);

		$env->processor()->multiplyByConstant($left, $constant, $result);

		$env->processor()->release($left);
	}
}