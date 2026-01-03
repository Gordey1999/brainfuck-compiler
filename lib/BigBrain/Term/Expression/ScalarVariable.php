<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\MemoryCellTyped;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class ScalarVariable implements Expression, Assignable
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

	public function resultType(Environment $env) : Type\Type
	{
		return $this->memoryCell($env)->type();
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$cell = $this->memoryCell($env);
		$env->processor()->copyNumber($cell, $result);
	}

	public function hasVariable(string $name) : bool
	{
		return $this->name()->value() === $name;
	}

	public function assign(Environment $env, Expression $value) : void
	{
		$value->compileCalculation($env, $this->memoryCell($env));
		// += -= (?)
		// TODO: Implement assign() method.
	}

	public function __toString() : string
	{
		return $this->lexeme()->value();
	}
}