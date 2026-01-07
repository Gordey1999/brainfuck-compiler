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

abstract class Skeleton implements Expression
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
		$this->assign($env);
	}

	protected abstract function assign(Environment $env) : void;

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$this->assign($env);
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