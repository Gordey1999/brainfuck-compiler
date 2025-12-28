<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;

class Subtraction extends Skeleton
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left - $right;
	}

	protected function compileForVariables(Environment $env, int $resultAddress) : void
	{
		$rightResultAddress = $env->processor()->reserve($resultAddress);

		$this->left->compileCalculation($env, $resultAddress);
		$this->right->compileCalculation($env, $rightResultAddress);

		$env->processor()->sub($resultAddress, $rightResultAddress);

		$env->processor()->release($rightResultAddress);
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, int $resultAddress) : void
	{
		$temp = $env->processor()->reserve($resultAddress);
		$this->right->compileCalculation($env, $temp);

		$env->processor()->addConstant($resultAddress, $constant);
		$env->processor()->sub($resultAddress, $temp);

		$env->processor()->release($temp);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, int $resultAddress) : void
	{
		$this->left->compileCalculation($env, $resultAddress);

		$env->processor()->subConstant($resultAddress, $constant);
	}
}