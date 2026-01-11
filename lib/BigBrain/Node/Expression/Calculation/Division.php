<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Calculation;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Token;

class Division
{
	public static function assignByConstant(Environment $env, MemoryCell $cell, int $constant, Token $expr) : void
	{
		if ($constant === 0) { throw new CompileError('division by zero', $expr); }
		if ($constant === 1) { return; }

		$temp = $env->processor()->reserve($cell);
		$env->processor()->move($cell, $temp);
		self::divideByConstant($env, $temp, $constant, $cell);
		$env->processor()->release($temp);
	}

	public static function assignByVariable(Environment $env, MemoryCell $cell, MemoryCell $value) : void
	{
		$temp = $env->processor()->reserve($cell, $value);
		$env->processor()->move($cell, $temp);
		self::divide($env, $temp, $value, $cell);
		$env->processor()->release($temp);
	}

	public static function divide(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$remainder = $proc->reserve($a, $b, $result);

		$proc->while($a, static function() use ($a, $b, $result, $remainder, $proc) {
			$proc->while($a, static function() use ($a, $b, $result, $remainder, $proc) {
				$temp = $proc->reserve($a, $b, $result);
				$proc->unset($remainder);
				$proc->copy($a, $remainder);
				$proc->copy($b, $temp);
				$proc->subUntilZero($a, $temp);
				$proc->increment($result);
				$proc->release($temp);
			}, "division cycle");

			$proc->sub($remainder, $b);
			$proc->if($remainder, static function () use ($result, $proc) {
				$proc->decrement($result);
			}, "if remainder > `0`, sub `1` from result");
		}, "$result = $a / $b (remainder: $remainder)");
		$proc->unset($b); // если $a ноль, то нужно обнулить $b

		$proc->release($remainder);
	}

	public static function divideByConstant(Environment $env, MemoryCell $a, int $constant, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$remainder = $proc->reserve($a, $result);

		$proc->while($a, static function() use ($a, $constant, $result, $remainder, $proc) {
			$proc->while($a, static function() use ($a, $constant, $result, $remainder, $proc) {
				$temp = $proc->reserve($a, $remainder, $result);
				$proc->unset($remainder);
				$proc->copy($a, $remainder);
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