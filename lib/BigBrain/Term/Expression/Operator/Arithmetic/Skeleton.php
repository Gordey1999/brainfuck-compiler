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

	protected abstract function compileForVariables(Environment $env, int $resultAddress) : void;

	protected abstract function compileWithLeftConstant(Environment $env, int $constant, int $resultAddress) : void;

	protected abstract function compileWithRightConstant(Environment $env, int $constant, int $resultAddress) : void;

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

		$this->checkComputedType($left);
		$this->checkComputedType($right);

		$result = $this->computeValue($left->getNumeric(), $right->getNumeric());

		return new Type\Computable($result);
	}

	protected function checkComputedType(Type\Computable $value) : void
	{
		if (!$value->numericCompatible())
		{
			throw new CompileError(sprintf('unsupported operand type "%s" for operator "%s"',
				$value->type(),
				$this->lexeme->value(),
			), $this->lexeme);
		}
	}

	public function compileCalculation(Environment $env, int $resultAddress) : void
	{
		if ($this->left->isComputable($env) && $this->right->isComputable($env))
		{
			throw new \Exception('not expected');
		}

		if ($this->left->isComputable($env))
		{
			$left = $this->left->compute($env);
			$this->checkComputedType($left);

			$this->compileWithLeftConstant($env, $left->getNumeric(), $resultAddress);
		}
		else if ($this->right->isComputable($env))
		{
			$right = $this->right->compute($env);
			$this->checkComputedType($right);

			$this->compileWithRightConstant($env, $right->getNumeric(), $resultAddress);
		}
		else
		{
			$this->compileForVariables($env, $resultAddress);
		}
	}

	public function hasVariable(string $name) : bool
	{
		return $this->left->hasVariable($name) || $this->right->hasVariable($name);
	}

	public function __toString() : string
	{
		return sprintf('(%s %s %s)', $this->left, $this->lexeme()->value(), $this->right);
	}
}