<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Subtraction extends Binary
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left - $right;
	}

	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		$rightResultAddress = $env->processor()->reserve($result);

		$this->left->compileCalculation($env, $result);
		$this->right->compileCalculation($env, $rightResultAddress);

		$env->processor()->sub($result, $rightResultAddress);

		$env->processor()->release($rightResultAddress);
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$temp = $env->processor()->reserve($result);
		$this->right->compileCalculation($env, $temp);

		$env->processor()->addConstant($result, $constant);
		$env->processor()->sub($result, $temp);

		$env->processor()->release($temp);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$this->left->compileCalculation($env, $result);

		$env->processor()->subConstant($result, $constant);
	}
}