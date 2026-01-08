<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Calculation;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;

class Multiplication
{
	public static function assignByConstant(Environment $env, MemoryCell $cell, int $constant) : void
	{
		$temp = $env->processor()->reserve($cell);
		$env->processor()->move($cell, $temp);
		$env->processor()->multiplyByConstant($temp, $constant, $cell);
		$env->processor()->release($temp);
	}

	public static function assignByVariable(Environment $env, MemoryCell $cell, MemoryCell $value) : void
	{
		$temp = $env->processor()->reserve($cell, $value);
		$env->processor()->move($cell, $temp);
		$env->processor()->multiply($temp, $value, $cell);
		$env->processor()->release($temp);
	}
}