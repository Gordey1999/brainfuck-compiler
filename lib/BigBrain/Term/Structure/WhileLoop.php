<?php

namespace Gordy\Brainfuck\BigBrain\Term\Structure;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Type;

class WhileLoop implements Term\Structure
{
	use Term\HasLexeme;

	protected Term\Expression $expression;
	protected Term\Scope $scope;

	public function __construct(Term\Expression $expression, Term\Scope $scope, Lexeme $lexeme)
	{
		$this->expression = $expression;
		$this->scope = $scope;
		$this->lexeme = $lexeme;
	}
	public function compile(Environment $env) : void
	{
		$env->stream()->blockComment($this);

		$exprType = $this->expression->resultType($env);


		if ($this->expression instanceof Term\Expression\ScalarVariable)
		{
			$cell = $this->expression->memoryCell($env);

			$env->processor()->while($cell, function() use ($env) {
				$this->scope->compile($env);
			}, "while $cell > 0");
		}
		else if ($exprType instanceof Type\Computable && $exprType->numericCompatible())
		{
			if ($exprType->getNumeric() === 0)
			{
				throw new CompileError('condition result is always false', $this->expression->lexeme());
			}
			else
			{
				throw new CompileError('infinite loop detected', $this->expression->lexeme());
			}
		}
		else if ($exprType instanceof Type\Scalar)
		{
			$condition = $env->processor()->reserve();
			$this->expression->compileCalculation($env, $condition);
			$env->processor()->while($condition, function() use ($env, $condition) {
				$this->scope->compile($env);
				$env->stream()->blockComment('recalculate condition');
				$env->processor()->unset($condition);
				$this->expression->compileCalculation($env, $condition);
			}, "while $condition > 0");

			$env->processor()->release($condition);
		}
		else
		{
			throw new CompileError('scalar condition expected', $this->expression->lexeme());
		}
	}

	public function __toString() : string
	{
		$expr = $this->expression;
		return "while ($expr)";
	}
}