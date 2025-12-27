<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Assignment;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use \Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\HasLexeme;
use Gordy\Brainfuck\BigBrain\Type;

class Base implements Expression
{
	use HasLexeme;

	protected Expression\Variable $variable;
	protected Expression $expression;


	public function __construct(Expression\Variable $variable, Expression $expr, Lexeme $lexeme)
	{
		$this->variable = $variable;
		$this->expression = $expr;
		$this->lexeme = $lexeme;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		$variables = $this->variables();
		$value = $this->value();
		foreach ($variables as $variable)
		{
			$address = $env->memory()->address($variable->name());

			$env->processor()->unset($address);
			if ($value instanceof Expression\Literal) // todo check type
			{
				$env->processor()->addConstant($address, $value->numberValue());
			}
			else
			{
				throw new CompileError('unexpected expression', $value->lexeme());
			}
		}
	}

	/** @return Expression\Variable[] */
	public function variables() : array
	{
		$result = [];
		if ($this->expression instanceof self) // a = b = c = 0;
		{
			$result = $this->expression->variables();
		}
		$result[] = $this->variable;

		return $result;
	}

	public function value() : Expression
	{
		if ($this->expression instanceof self) // a = b = c = 0;
		{
			return $this->expression->value();
		}
		return $this->expression;
	}

	public function calculate(Environment $env, int $resultAddress) : void
	{
		$env->processor()->unset($resultAddress);

		if ($this->expression instanceof Literal) // todo check type
		{
			$env->processor()->addConstant($resultAddress, $this->expression->numberValue());
		}
		else if ($this->expression instanceof self)
		{
			if ($this->expression->isConstant())
			{

			}
			$result = $this->expression->calculate($env);
		}
		else
		{
			throw new \Exception('not ready yet');
		}
		// unset
		// todo a = b = 3;
		// TODO: Implement calculate() method.
	}

	public function initialize(Environment $env, int $resultAddress) : void
	{

	}

	public function isComputable(Environment $env) : bool
	{
		return false;
	}

	public function compute(Environment $env) : Type\Computable
	{
		throw new \Exception('not implemented');
	}
}