<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Calculation;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\Expression\Assignable;

class Assignation
{
	public static function assignByConstant(
		Environment $env,
		MemoryCell $cell,
		int $constant,
		string $modifier,
		Lexeme $lexeme
	) : void
	{
		match ($modifier) {
			Assignable::ASSIGN_MULTIPLY => Multiplication::assignByConstant($env, $cell, $constant),
			Assignable::ASSIGN_DIVIDE => Division::assignByConstant($env, $cell, $constant, $lexeme),
			Assignable::ASSIGN_MODULO => Modulo::assignByConstant($env, $cell, $constant, $lexeme),
			default => throw new CompileError('undefined modifier', $lexeme),
		};
	}

	public static function assignByVariable(
		Environment $env,
		MemoryCell $cell,
		MemoryCell $value,
		string $modifier,
		Lexeme $lexeme
	) : void
	{
		match ($modifier) {
			Assignable::ASSIGN_MULTIPLY => Multiplication::assignByVariable($env, $cell, $value),
			Assignable::ASSIGN_DIVIDE => Division::assignByVariable($env, $cell, $value, $lexeme),
			Assignable::ASSIGN_MODULO => Modulo::assignByVariable($env, $cell, $value, $lexeme),
			default => throw new CompileError('undefined modifier', $lexeme),
		};
	}
}