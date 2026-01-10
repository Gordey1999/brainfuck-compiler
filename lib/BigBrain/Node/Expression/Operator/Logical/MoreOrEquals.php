<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class MoreOrEquals extends More
{
	protected function computeValue(int $left, int $right) : bool
	{
		return $left >= $right;
	}

	protected function calculate(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$proc->subUntilZero($b, $a);
		$proc->moveBoolean($b, $a);
		$proc->not($a);
		$proc->moveBoolean($a, $result);
	}

	public function __toString() : string
	{
		return sprintf('(%s >= %s)', $this->left, $this->right);
	}
}