<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;

class Addition extends Skeleton
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left + $right;
	}

	protected function compileForVariables(Environment $env, int $resultAddress) : void
	{
		$rightResultAddress = $env->processor()->reserve($resultAddress);

		$this->left->compileCalculation($env, $resultAddress);
		$this->right->compileCalculation($env, $rightResultAddress);

		$env->processor()->add($rightResultAddress, $resultAddress);

		$env->processor()->release($rightResultAddress);
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, int $resultAddress) : void
	{
		$this->right->compileCalculation($env, $resultAddress);

		$this->compileForConstant($env, $constant, $resultAddress);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, int $resultAddress) : void
	{
		$this->left->compileCalculation($env, $resultAddress);

		$this->compileForConstant($env, $constant, $resultAddress);
	}

	protected function compileForConstant(Environment $env, int $constant, int $resultAddress) : void
	{
		$env->processor()->addConstant($resultAddress, $constant);
	}
}