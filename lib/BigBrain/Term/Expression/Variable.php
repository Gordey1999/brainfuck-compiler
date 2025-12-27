<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class Variable implements Expression
{
	use BigBrain\Term\HasLexeme;

	public function __construct(Lexeme $name)
	{
		$this->lexeme = $name;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		// do nothing
	}

	public function name() : Lexeme
	{
		return $this->lexeme;
	}

	public function calculate(Environment $env, int $resultAddress) : void
	{
		// TODO: Implement calculate() method.
	}

	public function isComputable(Environment $env) : bool
	{
		return false;
	}

	public function compute(Environment $env) : Type\Computable
	{
		throw new \Exception('not implemented');
	}
}