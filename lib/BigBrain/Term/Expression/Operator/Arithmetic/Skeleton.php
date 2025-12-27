<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;

abstract class Skeleton implements Expression
{
	use Term\HasLexeme;

	protected Expression $left;
	protected Expression $right;

	public function __construct(Expression $left, Expression $right, Lexeme $lexeme)
	{
		$this->left = $left;
		$this->right = $right;
		$this->lexeme = $lexeme;
	}

	protected abstract function computeValue(int $left, int $right) : int;

	public function calculate(Environment $env, int $resultAddress) : void
	{
		throw new \Exception('not implemented');
	}

	public function isComputable(Environment $env) : bool
	{
		return $this->left->isComputable($env) && $this->right->isComputable($env);
	}

	public function compile(Environment $env) : void
	{
		// do nothing
	}

	public function compute(Environment $env) : Type\Computable
	{
		$left = $this->left->compute($env);
		$right = $this->right->compute($env);

		if (!$left->numericCompatible() || !$right->numericCompatible())
		{
			throw new CompileError(sprintf('incompatible operand types %s %s %s',
				$left->type(),
				$this->lexeme->value(),
				$right->type(),
			), $this->lexeme);
		}

		$result = $this->computeValue($left->getNumeric(), $right->getNumeric());

		return new Type\Computable($result);
	}
}