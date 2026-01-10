<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class More extends Binary
{
	protected function computeValue(int $left, int $right) : bool
	{
		return $left > $right;
	}

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
		$right = $env->processor()->reserve($result);
		$this->right->compileCalculation($env, $right);
		$left = $env->processor()->reserve($result);
		$env->processor()->addConstant($left, $constant);

		$this->calculate($env, $left, $right, $result);
		$env->processor()->release($left, $right);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);
		$right = $env->processor()->reserve($result);
		$env->processor()->addConstant($right, $constant);

		$this->calculate($env, $left, $right, $result);
		$env->processor()->release($left, $right);
	}

	protected function calculate(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$proc->subUntilZero($a, $b);
		$proc->moveBoolean($a, $result);
	}

	public function __toString() : string
	{
		return sprintf('(%s > %s)', $this->left, $this->right);
	}
}