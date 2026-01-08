<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Both extends Commutative
{
	protected function computeValue(int $left, int $right) : bool
	{
		return $left && $right;
	}

	protected function calculateWithConstant(Environment $env, MemoryCell $value, int $constant, MemoryCell $result) : void
	{
		$proc = $env->processor();
		if ($constant > 0)
		{
			$proc->moveBoolean($value, $result);
		}
		else
		{
			$proc->unset($value);
		}
	}

	protected function calculate(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$proc->if($a, function() use ($proc, $b, $result) {
			$proc->moveBoolean($b, $result);
		}, "$result = $a && $b");
		$proc->unset($b);
	}

	public function __toString() : string
	{
		return sprintf('(%s && %s)', $this->left, $this->right);
	}
}