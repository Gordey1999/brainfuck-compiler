<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;

class Literal implements Expression
{
	use BigBrain\Term\HasLexeme;

	public function __construct(Lexeme $lexeme)
	{
		$this->lexeme = $lexeme;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		// do nothing
	}

	public function resultType(Environment $env) : Type\Computable
	{
		$value = $this->lexeme->value();
		$parsed = match(true) {
			$value[0] === '"' || $value[0] === "'" => substr($value, 1, -1),
			ctype_digit($value) => (int)$value,
			default => throw new CompileError('not supported type', $value),
		};

		return new Type\Computable($parsed);
	}

	public function compileCalculation(Environment $env, int $resultAddress) : void
	{
		$env->processor()->addConstant($resultAddress, $this->resultType($env)->getNumeric());
	}

	public function hasVariable(string $name) : bool
	{
		return false;
	}

	public function __toString() : string
	{
		return $this->lexeme()->value();
	}
}