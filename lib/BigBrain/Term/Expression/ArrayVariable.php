<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\MemoryCellArray;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class ArrayVariable implements Expression
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

	public function memoryCell(Environment $env) : MemoryCellArray
	{
		return $env->arraysMemory()->get($this->name());
	}

	public function resultType(Environment $env) : Type\Type
	{
		return $this->memoryCell($env)->type();
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		throw new CompileError('scalar type expected', $this->name());
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