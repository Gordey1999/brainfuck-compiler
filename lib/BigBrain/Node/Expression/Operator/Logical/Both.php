<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Both extends Commutative
{
	protected function computeValue(int $left, int $right) : bool
	{
		return $left && $right;
	}

	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		$proc = $env->processor();

		$left = $proc->reserve($result);
		$this->left->compileCalculation($env, $left);

		$proc->if($left, function() use ($proc, $env, $result) {
			$right = $proc->reserve($result);
			$this->right->compileCalculation($env, $right);
			$proc->moveBoolean($right, $result);
			$proc->release($right);
		}, "if $left");

		$proc->release($left);
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
		throw new \Exception('Not implemented');
	}

	public function __toString() : string
	{
		return sprintf('(%s && %s)', $this->left, $this->right);
	}
}