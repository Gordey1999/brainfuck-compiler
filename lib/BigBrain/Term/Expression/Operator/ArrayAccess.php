<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use \Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\HasLexeme;
use Gordy\Brainfuck\BigBrain\Type;

class ArrayAccess implements Expression
{
	use HasLexeme;

	protected Expression $to;
	protected Expression $index;

	public function __construct(Expression $to, Expression $index, Lexeme $lexeme)
	{
		$this->to = $to;
		$this->index = $index;
		$this->lexeme = $lexeme;
	}

	public function variableName() : Lexeme
	{
		if ($this->to instanceof Expression\Variable)
		{
			return $this->to->name();
		}
		if ($this->to instanceof self)
		{
			return $this->to->variableName();
		}
		throw new SyntaxError('variable expected', $this->to->lexeme());
	}

	public function dimensions(Environment $env) : array
	{
		$resultType = $this->index->resultType($env);

		if (!$resultType instanceof Type\Computable)
		{
			throw new CompileError('dynamic expressions not allowed here', $this->index->lexeme());
		}
		if (!$resultType->numericNullableCompatible())
		{
			throw new CompileError('numeric expected', $this->index->lexeme());
		}

		if ($this->to instanceof self)
		{
			return array_merge(
				$this->to->dimensions($env),
				[ $resultType->getNumericNullable() ]
			);
		}
		return [$resultType->getNumericNullable()];
	}

	public function resultType(Environment $env) : Type\Type
	{
		throw new \Exception('not implemented');
	}

	public function compile(BigBrain\Environment $env) : void
	{
		throw new \Exception('not implemented');
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		throw new \Exception('not implemented');
	}

	public function hasVariable(string $name) : bool
	{
		return false;// todo [a]
	}

	public function __toString() : string
	{
		return sprintf('%s[%s]', $this->to, $this->index);
	}
}