<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;

class Comma implements Expression
{
	use BigBrain\Term\HasLexeme;

	protected Expression $left;
	protected Expression $right;

	public function __construct(Expression $left, Expression $right, Lexeme $lexeme)
	{
		$this->left = $left;
		$this->right = $right;
		$this->lexeme = $lexeme;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		throw new CompileError('unexpected operator ","', $this->lexeme);
	}

	/** @return Expression[] */
	public function list() : array
	{
		$result = [];
		if ($this->left instanceof self)
		{
			$result = array_merge($result, $this->left->list());
		}
		else
		{
			$result[] = $this->left;
		}

		if ($this->right instanceof self)
		{
			$result = array_merge($result, $this->right->list());
		}
		else
		{
			$result[] = $this->right;
		}

		return $result;
	}

	public function resultType(Environment $env) : Type\Type
	{
		throw new CompileError('unexpected operator ","', $this->lexeme);
	}

	public function compileCalculation(Environment $env, int $resultAddress) : void
	{
		throw new \Exception('not implemented');
	}

	public function hasVariable(string $name) : bool
	{
		return $this->left->hasVariable($name) || $this->right->hasVariable($name);
	}

	public function __toString() : string
	{
		return sprintf('%s, %s', $this->left, $this->right);
	}
}