<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class One extends Commutative
{
	protected function computeValue(int $left, int $right) : bool
	{
		return $left || $right;
	}

	protected function calculateWithConstant(Environment $env, MemoryCell $value, int $constant, MemoryCell $result) : void
	{
		$proc = $env->processor();
		if ($constant > 0)
		{
			$proc->unset($value);
			$proc->addConstant($result, 1);
		}
		else
		{
			$proc->moveBoolean($value, $result);
		}
	}

	protected function calculate(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$proc->moveBoolean($a, $result);
		$proc->moveBoolean($b, $result);
	}

	public function __toString() : string
	{
		return sprintf('(%s || %s)', $this->left, $this->right);
	}
}