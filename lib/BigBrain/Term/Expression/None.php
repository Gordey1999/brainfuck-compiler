<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class None implements Expression
{
	public function resultType(Environment $env) : Type\Type
	{
		return new Type\Computable(null);
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

	public function token() : Token
	{
		throw new \Exception('Not implemented');
	}

	public function __toString() : string
	{
		return '';
	}
}