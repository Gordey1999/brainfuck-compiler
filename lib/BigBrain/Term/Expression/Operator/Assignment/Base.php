<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use \Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\HasLexeme;
use Gordy\Brainfuck\BigBrain\Type;

class Base implements Expression
{
	use HasLexeme;

	protected Expression\Assignable $to;
	protected Expression $value;

	public function __construct(Expression $to, Expression $expr, Lexeme $lexeme)
	{
		if (!$to instanceof Expression\Assignable)
		{
			throw new CompileError('assignable value expected', $to->lexeme());
		}

		$this->to = $to;
		$this->value = $expr;
		$this->lexeme = $lexeme;
	}

	public function resultType(Environment $env) : Type\Type
	{
		return $this->to->resultType($env);
	}

	public function compile(BigBrain\Environment $env) : void
	{
		$env->stream()->blockComment($this);
		$this->to->assign($env, $this->value, Expression\Assignable::ASSIGN_SET);
	}

	/** @return Expression\ScalarVariable[] */
	public function variables() : array
	{
		$result = [ $this->to ];
		if ($this->value instanceof self) // a = (b = (c = 0));
		{
			array_push($result, ...$this->value->variables());
		}

		return $result;
	}

	public function left() : Expression
	{
		return $this->to;
	}

	public function right() : Expression
	{
		return $this->value;
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$this->compile($env);
		$this->to->compileCalculation($env, $result);
	}

	public function hasVariable(string $name) : bool
	{
		return $this->value->hasVariable($name);
	}

	public function __toString() : string
	{
		return sprintf('%s = %s', $this->to, $this->value);
	}
}