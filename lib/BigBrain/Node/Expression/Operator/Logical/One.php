<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Type;

class One extends Commutative
{
	protected function computeValue(int $left, int $right) : bool
	{
		return $left || $right;
	}

	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		$proc = $env->processor();

		[$left, $right] = $proc->reserveSeveral(2, $result);
		$this->left->compileCalculation($env, $left);
		$proc->copy($left, $result);
		$proc->moveBoolean($result, $right);

		$proc->if($left, function() use ($proc, $env, $result) {
			$proc->addConstant($result, 1);
		}, "if $left");

		$proc->not($right);
		$proc->if($right, function() use ($proc, $env, $right, $result) {
			$this->right->compileCalculation($env, $right);
			$proc->moveBoolean($right, $result);
		}, "else");

		$proc->release($left, $right);
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
		throw new \Exception('Not implemented');
	}

	public function __toString() : string
	{
		return sprintf('(%s || %s)', $this->left, $this->right);
	}
}