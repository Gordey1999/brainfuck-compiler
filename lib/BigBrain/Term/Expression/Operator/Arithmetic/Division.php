<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Division extends Binary
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left / $right;
	}

	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);
		$right = $env->processor()->reserve($left, $result);
		$this->right->compileCalculation($env, $right);

		$this->divide($env, $left, $right, $result);
		$env->processor()->release($left, $right);
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		if ($constant === 0) { return; }

		$left = $env->processor()->reserve($result);
		$env->processor()->addConstant($left, $constant);
		$right = $env->processor()->reserve($left, $result);
		$this->right->compileCalculation($env, $right);

		$this->divide($env, $left, $right, $result);
		$env->processor()->release($left, $right);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		if ($constant === 0) { throw new CompileError('division by zero', $this->lexeme()); }
		if ($constant === 1)
		{
			$this->left->compileCalculation($env, $result);
			return;
		}

		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);

		$this->divideByConstant($env, $left, $constant, $result);
		$env->processor()->release($left);
	}

	protected function divide(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$remainder = $proc->reserve($a, $b, $result);

		$proc->while($a, static function() use ($a, $b, $result, $remainder, $proc) {
			$proc->while($a, static function() use ($a, $b, $result, $remainder, $proc) {
				$temp = $proc->reserve($a, $b, $result);
				$proc->unset($remainder);
				$proc->copyNumber($a, $remainder);
				$proc->copyNumber($b, $temp);
				$proc->subUntilZero($a, $temp);
				$proc->increment($result);
				$proc->release($temp);
			}, "division cycle");

			$proc->sub($remainder, $b);
			$proc->if($remainder, static function () use ($result, $proc) {
				$proc->decrement($result);
			}, "if remainder > `0`, sub `1` from result");
		}, "$result = $a / $b (remainder: $remainder)");

		$proc->release($remainder);
	}

	protected function divideByConstant(Environment $env, MemoryCell $a, int $constant, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$remainder = $proc->reserve($a, $result);

		$proc->while($a, static function() use ($a, $constant, $result, $remainder, $proc) {
			$proc->while($a, static function() use ($a, $constant, $result, $remainder, $proc) {
				$temp = $proc->reserve($a, $remainder, $result);
				$proc->unset($remainder);
				$proc->copyNumber($a, $remainder);
				$proc->addConstant($temp, $constant);
				$proc->subUntilZero($a, $temp);
				$proc->increment($result);
				$proc->release($temp);
			}, "division cycle");

			$proc->subConstant($remainder, $constant);
			$proc->if($remainder, static function () use ($result, $proc) {
				$proc->decrement($result);
			}, "if remainder > `0`, sub `1` from quotient");
		}, "$result = $a / `$constant` (remainder: $remainder)");

		$proc->release($remainder);
	}
}