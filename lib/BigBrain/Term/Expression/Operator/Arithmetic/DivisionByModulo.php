<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class DivisionByModulo extends Skeleton
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left % $right;
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
		if ($constant === 0) { return; }

		[$left, $right] = $env->processor()->reserveSeveral(2, $result);

		$env->processor()->addConstant($left, $constant);
		$this->right->compileCalculation($env, $right);

		$this->divide($env, $left, $right, $result);

		$env->processor()->release($left, $right);
	}

	protected function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void
	{
		if ($constant === 0) { throw new CompileError('division by zero', $this->lexeme()); }
		if ($constant === 1) { return; }

		$left = $env->processor()->reserve($result);
		$this->left->compileCalculation($env, $left);

		$this->divideByConstant($env, $left, $constant, $result);

		$env->processor()->release($left);
	}

	protected function divide(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$temp = $proc->reserve($a, $b, $result);

		$proc->while($a, static function() use ($a, $b, $result, $temp, $proc) {
			$proc->while($a, static function() use ($a, $b, $result, $temp, $proc) {
				$proc->unset($result);
				$proc->copyNumber($a, $result);
				$proc->copyNumber($b, $temp);
				$proc->subUntilZero($a, $temp);
			}, "division cycle");

			$proc->copyNumber($result, $a);
			$proc->equals($a, $b, $temp);
			$proc->if($temp, static function () use ($result, $proc) {
				$proc->unset($result);
			}, "if result === divider, unset remainder");
		}, "$a % $b (result: $result)");

		$proc->release($temp);
	}

	protected function divideByConstant(Environment $env, MemoryCell $a, int $constant, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$temp = $proc->reserve($a, $result);

		$proc->while($a, static function() use ($a, $constant, $result, $temp, $proc) {
			$proc->while($a, static function() use ($a, $constant, $result, $temp, $proc) {
				$proc->unset($result);
				$proc->copyNumber($a, $result);
				$proc->addConstant($temp, $constant);
				$proc->subUntilZero($a, $temp);
			}, "division cycle");

			$proc->copyNumber($result, $a);
			$proc->equalsToConstant($a, $constant, $temp);
			$proc->if($temp, static function () use ($result, $proc) {
				$proc->unset($result);
			}, "if result === divider, unset remainder");
		}, "$a % `$constant` (result: $result)");

		$proc->release($temp);
	}
}