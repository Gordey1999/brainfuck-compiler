<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Division extends Skeleton
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left / $right;
	}

	protected function compileForVariables(Environment $env, MemoryCell $result) : void
	{
		[$left, $right] = $env->processor()->reserveSeveral(2, $result);

		$this->left->compileCalculation($env, $left);
		$this->right->compileCalculation($env, $right);

		$this->divide($env, $left, $right, $result);

		$env->processor()->release($left, $right);
	}

	protected function compileWithLeftConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		[$left, $right] = $env->processor()->reserveSeveral(2, $result);

		$env->processor()->addConstant($left, $constant);
		$this->right->compileCalculation($env, $right);

		$this->divide($env, $left, $right, $result);

		$env->processor()->release($left, $right);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);

		$this->divideByConstant($env, $left, $constant, $result);

		$env->processor()->release($left);
	}

	protected function divide(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		[$temp, $remainder] = $proc->reserveSeveral(2, $a, $b, $result);

		$proc->while($a, static function() use ($a, $b, $result, $remainder, $temp, $proc) {
			$proc->while($a, static function() use ($a, $b, $result, $remainder, $temp, $proc) {
				$proc->unset($remainder);
				$proc->copyNumber($a, $remainder);
				$proc->copyNumber($b, $temp);
				$proc->subUntilZero($a, $temp);
				$proc->increment($result);
			}, "division cycle");

			$proc->sub($remainder, $b);
			$proc->if($remainder, static function () use ($result, $proc) {
				$proc->decrement($result);
			}, "if remainder > `0`, sub `1` from result");
		}, "$a / $b (result: $result, remainder: $remainder)");

		$proc->release($temp, $remainder);
	}

	protected function divideByConstant(Environment $env, MemoryCell $a, int $constant, MemoryCell $result) : void
	{
		$proc = $env->processor();
		[$temp, $remainder] = $proc->reserveSeveral(2, $a, $result);

		$proc->while($a, static function() use ($a, $constant, $result, $remainder, $temp, $proc) {
			$proc->while($a, static function() use ($a, $constant, $result, $remainder, $temp, $proc) {
				$proc->unset($remainder);
				$proc->copyNumber($a, $remainder);
				$proc->addConstant($temp, $constant);
				$proc->subUntilZero($a, $temp);
				$proc->increment($result);
			}, "division cycle");

			$proc->subConstant($remainder, $constant);
			$proc->if($remainder, static function () use ($result, $proc) {
				$proc->decrement($result);
			}, "if remainder > `0`, sub `1` from quotient");
		}, "$a / `$constant` (result: $result, remainder: $remainder)");

		$proc->release($temp, $remainder);
	}
}