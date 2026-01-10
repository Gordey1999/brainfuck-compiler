<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Equals extends Commutative
{
	protected function computeValue(int $left, int $right) : bool
	{
		return $left === $right;
	}

	protected function calculateWithConstant(Environment $env, MemoryCell $value, int $constant, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$temp = $env->processor()->reserve($value, $result);

		$proc->subConstant($value, $constant);
		$proc->moveBoolean($value, $temp);
		$proc->not($temp);
		$proc->moveBoolean($temp, $result);

		$env->processor()->release($temp);
	}

	protected function calculate(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();

		$proc->sub($a, $b);
		$proc->moveBoolean($a, $b);
		$proc->not($b);
		$proc->moveBoolean($b, $result);
	}

	public function __toString() : string
	{
		return sprintf('(%s == %s)', $this->left, $this->right);
	}
}