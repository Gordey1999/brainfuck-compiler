<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator;

use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term\Expression;

abstract class Binary implements Expression
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

	protected abstract function computeValue(int $left, int $right) : mixed;

	protected abstract function compileForVariables(Environment $env, MemoryCell $result) : void;

	protected abstract function compileWithLeftConstant(Environment $env, int $constant, MemoryCell $result) : void;

	protected abstract function compileWithRightConstant(Environment $env, int $constant, MemoryCell $result) : void;

	protected abstract function computeResultType() : Type\BaseType;

	public function compile(Environment $env) : void
	{
		// do nothing
	}

	public function resultType(Environment $env) : Type\Type
	{
		$leftType = $this->left->resultType($env);
		$rightType = $this->right->resultType($env);

		if ($leftType instanceof Type\Computable && $rightType instanceof Type\Computable)
		{
			$this->checkComputedType($leftType);
			$this->checkComputedType($rightType);

			$result = $this->computeValue($leftType->getNumeric(), $rightType->getNumeric());
			return new Type\Computable($result);
		}

		return $this->computeResultType();
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$resultType = $this->resultType($env);
		if ($resultType instanceof Type\Computable)
		{
			$env->processor()->addConstant($result, $resultType->value());
			return;
		}

		$leftType = $this->left->resultType($env);
		$rightType = $this->right->resultType($env);

		if ($leftType instanceof Type\Computable)
		{
			$this->checkComputedType($leftType);
			$this->checkScalarType($rightType);
			$this->compileWithLeftConstant($env, $leftType->getNumeric(), $result);
		}
		else if ($rightType instanceof Type\Computable)
		{
			$this->checkComputedType($rightType);
			$this->checkScalarType($leftType);
			$this->compileWithRightConstant($env, $rightType->getNumeric(), $result);
		}
		else
		{
			$this->compileForVariables($env, $result);
		}
	}

	public function hasVariable(string $name) : bool
	{
		return $this->left->hasVariable($name) || $this->right->hasVariable($name);
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

	protected function checkScalarType(Type\Type $value) : void
	{
		if (!$value instanceof Type\Scalar)
		{
			throw new CompileError(sprintf('unsupported operand type "%s" for operator "%s"',
				$value,
				$this->lexeme->value(),
			), $this->lexeme);
		}
	}
}