<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

abstract class Commutative extends Binary
{
	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);
		$right = $env->processor()->reserve($result);
		$this->right->compileCalculation($env, $right);

		$this->calculate($env, $left, $right, $result);
		$env->processor()->release($left, $right);
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$temp = $env->processor()->reserve($result);
		$this->right->compileCalculation($env, $temp);

		$this->calculateWithConstant($env, $temp, $constant, $result);
		$env->processor()->release($temp);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$temp = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $temp);

		$this->calculateWithConstant($env, $temp, $constant, $result);
		$env->processor()->release($temp);
	}

	protected abstract function calculateWithConstant(Environment $env, MemoryCell $value, int $constant, MemoryCell $result) : void;

	protected abstract function calculate(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void;
}