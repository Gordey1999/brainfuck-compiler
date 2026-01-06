<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class Increment implements Expression
{
	use Term\HasLexeme;

	protected Expression\Assignable $to;
	protected Expression $value;
	protected bool $isPost;

	public function __construct(Expression $to, Lexeme $lexeme, bool $isPost = false)
	{
		if (!$to instanceof Expression\Assignable)
		{
			throw new CompileError('assignable value expected', $to->lexeme());
		}
		$this->to = $to;
		$this->value = new Expression\Literal(
			new Lexeme('1', $lexeme->index(), $lexeme->position())
		);
		$this->isPost = $isPost;
		$this->lexeme = $lexeme;
	}

	public function compile(Environment $env) : void
	{
		$env->stream()->blockComment($this);
		$this->to->assign($env, $this->value, Expression\Assignable::ASSIGN_ADD);
	}

	public function resultType(Environment $env) : Type\Type
	{
		return $this->to->resultType($env);
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		if ($this->isPost)
		{
			$this->to->compileCalculation($env, $result);
			$this->compile($env);
		}
		else
		{
			$this->compile($env);
			$this->to->compileCalculation($env, $result);
		}
	}

	public function hasVariable(string $name) : bool
	{
		return $this->to->hasVariable($name);
	}

	public function __toString() : string
	{
		return sprintf('++%s', $this->to);
	}
}