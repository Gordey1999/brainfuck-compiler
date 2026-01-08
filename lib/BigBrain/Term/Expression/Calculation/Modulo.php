<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Calculation;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Modulo
{
	public static function assignByConstant(Environment $env, MemoryCell $cell, int $constant, $lexeme) : void
	{
		if ($constant === 0) { throw new CompileError('division by zero', $lexeme); }
		if ($constant === 1)
		{
			$env->processor()->unset($cell);
		}

		$temp = $env->processor()->reserve($cell);
		$env->processor()->move($cell, $temp);
		self::divideByConstant($env, $temp, $constant, $cell);
		$env->processor()->release($temp);
	}

	public static function assignByVariable(Environment $env, MemoryCell $cell, MemoryCell $value) : void
	{

		// todo $env->memory()->index($cell) > 10 с массивами не сработает
		$temp = $env->processor()->reserve($cell, $value);
		$env->processor()->move($cell, $temp);
		self::divide($env, $temp, $value, $cell);
		$env->processor()->release($temp);
	}

	public static function divide(Environment $env, MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$temp = $proc->reserve($a, $b, $result);

		$proc->while($a, static function() use ($a, $b, $result, $temp, $proc) {
			$proc->while($a, static function() use ($a, $b, $result, $temp, $proc) {
				$proc->unset($result);
				$proc->copy($a, $result);
				$proc->copy($b, $temp);
				$proc->subUntilZero($a, $temp);
			}, "division cycle");

			$proc->copy($result, $a);
			$proc->equals($a, $b, $temp);
			$proc->if($temp, static function () use ($result, $proc) {
				$proc->unset($result);
			}, "if result === divider, unset remainder");
		}, "$result = $a % $b");

		$proc->release($temp);
	}

	public static function divideByConstant(Environment $env, MemoryCell $a, int $constant, MemoryCell $result) : void
	{
		$proc = $env->processor();
		$temp = $proc->reserve($a, $result);

		$proc->while($a, static function() use ($a, $constant, $result, $temp, $proc) {
			$proc->while($a, static function() use ($a, $constant, $result, $temp, $proc) {
				$proc->unset($result);
				$proc->copy($a, $result);
				$proc->addConstant($temp, $constant);
				$proc->subUntilZero($a, $temp);
			}, "division cycle");

			$proc->copy($result, $a);
			$proc->equalsToConstant($a, $constant, $temp);
			$proc->if($temp, static function () use ($result, $proc) {
				$proc->unset($result);
			}, "if result === divider, unset remainder");
		}, "$result = $a % `$constant`");

		$proc->release($temp);
	}
}