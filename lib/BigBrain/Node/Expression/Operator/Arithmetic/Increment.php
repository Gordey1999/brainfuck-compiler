<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Node;
use Gordy\Brainfuck\BigBrain\Node\Expression;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Token;

class Increment implements Expression
{
	use Node\HasToken;

	protected Expression\Assignable $to;
	protected Expression $value;
	protected bool $isPost;

	public function __construct(Expression $to, Token $token, bool $isPost = false)
	{
		if (!$to instanceof Expression\Assignable)
		{
			throw new CompileError('assignable value expected', $to->token());
		}
		$this->to = $to;
		$this->value = new Expression\Literal(
			new Token('1', $token->index(), $token->position())
		);
		$this->isPost = $isPost;
		$this->token = $token;
	}

	public function compile(Environment $env) : void
	{
		$env->stream()->blockComment($this);
		$this->calculate($env);
	}

	protected function calculate(Environment $env) : void
	{
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
			$this->calculate($env);
		}
		else
		{
			$this->calculate($env);
			$this->to->compileCalculation($env, $result);
		}
	}

	public function hasVariable(string $name) : bool
	{
		return $this->to->hasVariable($name);
	}

	public function __toString() : string
	{
		if ($this->isPost)
		{
			return sprintf('%s++', $this->to);
		}
		else
		{
			return sprintf('++%s', $this->to);
		}
	}
}