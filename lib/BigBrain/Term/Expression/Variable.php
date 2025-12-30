<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\MemoryCellTyped;
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

	public function memoryCell(Environment $env) : MemoryCellTyped
	{
		return $env->memory()->get($this->name());
	}

	public function resultType(Environment $env) : Type\BaseType
	{
		return $env->memory()->get($this->name())->type();
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$cell = $env->memory()->get($this->lexeme);
		$env->processor()->copyNumber($cell, $result);
	}

	public function hasVariable(string $name) : bool
	{
		return $this->name()->value() === $name;
	}

	public function __toString() : string
	{
		return $this->lexeme()->value();
	}
}