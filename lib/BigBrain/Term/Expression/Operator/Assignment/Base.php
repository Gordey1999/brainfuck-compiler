<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
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
		$env->stream()->blockComment($this);
		$variables = $this->variables();
		$value = $this->value();
		foreach ($variables as $variable)
		{
			$address = $env->memory()->address($variable->name());

			if ($value->isComputable($env))
			{
				$computed = $value->compute($env); // todo check type !!!!!!!!!!

				$env->processor()->unset($address);
				$env->processor()->addConstant($address, $computed->getNumeric());
			}
			else
			{
				// todo приведение типов !!!!!

				if ($value->hasVariable($variable->name()->value()))
				{
					$tempResult = $env->processor()->reserve($address);

					$value->compileCalculation($env, $tempResult);

					$env->processor()->unset($address);
					$env->processor()->moveNumber($tempResult, $address);
					$env->processor()->release($tempResult);
				}
				else
				{
					$env->processor()->unset($address);
					$value->compileCalculation($env, $address);
				}


				// todo если переменная присутствует в выражении, надо скопировать
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

	public function compileCalculation(Environment $env, int $resultAddress) : void
	{
		$this->compile($env);
	}

	public function isComputable(Environment $env) : bool
	{
		return false;
	}

	public function compute(Environment $env) : Type\Computable
	{
		throw new \Exception('not implemented');
	}

	public function hasVariable(string $name) : bool
	{
		return false;
	}

	public function __toString() : string
	{
		return sprintf('%s = %s', $this->variable, $this->expression);
	}
}