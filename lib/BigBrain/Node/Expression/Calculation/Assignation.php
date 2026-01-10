<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Calculation;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Node\Expression\Assignable;

class Assignation
{
	public static function assignByConstant(
		Environment $env,
		MemoryCell $cell,
		int $constant,
		string $modifier,
		Token $token
	) : void
	{
		match ($modifier) {
			Assignable::ASSIGN_MULTIPLY => Multiplication::assignByConstant($env, $cell, $constant),
			Assignable::ASSIGN_DIVIDE => Division::assignByConstant($env, $cell, $constant, $token),
			Assignable::ASSIGN_MODULO => Modulo::assignByConstant($env, $cell, $constant, $token),
			default => throw new CompileError('undefined modifier', $token),
		};
	}

	public static function assignByVariable(
		Environment $env,
		MemoryCell $cell,
		MemoryCell $value,
		string $modifier,
		Token $token
	) : void
	{
		match ($modifier) {
			Assignable::ASSIGN_MULTIPLY => Multiplication::assignByVariable($env, $cell, $value),
			Assignable::ASSIGN_DIVIDE => Division::assignByVariable($env, $cell, $value, $token),
			Assignable::ASSIGN_MODULO => Modulo::assignByVariable($env, $cell, $value, $token),
			default => throw new CompileError('undefined modifier', $token),
		};
	}
}