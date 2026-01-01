<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class None implements Expression
{
	public function resultType(Environment $env) : Type\Type
	{
		throw new \Exception('Not implemented');
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		throw new \Exception('Not implemented');
	}

	public function hasVariable(string $name) : bool
	{
		throw new \Exception('Not implemented');
	}

	public function compile(Environment $env) : void
	{
		throw new \Exception('Not implemented');
	}

	public function lexeme() : Lexeme
	{
		throw new \Exception('Not implemented');
	}

	public function __toString() : string
	{
		return '';
	}
}