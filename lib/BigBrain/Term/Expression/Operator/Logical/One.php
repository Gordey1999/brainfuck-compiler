<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Type;

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
		if ($this->left->resultType($env) instanceof Type\Boolean
			&& $this->right->resultType($env) instanceof Type\Boolean)
		{
			$proc->add($a, $b);
			$proc->moveBoolean($b, $result);
		}
		else
		{
			$temp = $proc->reserve($a, $b, $result);
			$proc->moveBoolean($a, $temp);
			$proc->moveBoolean($b, $temp);
			$proc->moveBoolean($temp, $result);
			$proc->release($temp);
		}
	}

	public function __toString() : string
	{
		return sprintf('(%s || %s)', $this->left, $this->right);
	}
}