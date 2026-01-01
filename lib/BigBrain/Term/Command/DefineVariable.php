<?php

namespace Gordy\Brainfuck\BigBrain\Term\Command;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\Expression\Variable;
use Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;
use Gordy\Brainfuck\BigBrain\Term\Expression\Operator\ArrayAccess;

class DefineVariable implements Term\Command
{
	use Term\HasLexeme;

	private BigBrain\Type\BaseType $type;

	/** @var Expression[] $variables */
	private array $variables;

	public function __construct(BigBrain\Type\BaseType $type, Expression $expr, Lexeme $lexeme)
	{
		$this->type = $type;
		$this->variables = $this->getVariableList($expr);
		$this->lexeme = $lexeme;
	}

	protected function getVariableList(Expression $expr) : array
	{
		if ($expr instanceof Expression\Operator\Comma)
		{
			$varList = $expr->list();
		}
		else
		{
			$varList = [ $expr ];
		}

		foreach ($varList as $var)
		{
			if (!$var instanceof Variable && !$var instanceof Assignment\Base && !$var instanceof ArrayAccess)
			{
				throw new SyntaxError('variable name expected', $var->lexeme());
			}
		}

		return $varList;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		foreach ($this->variables as $expression)
		{
			if ($expression instanceof Assignment\Base)
			{
				if ($expression->left() instanceof ArrayAccess)
				{
					$array = $expression->left();
					$name = $array->variableName();
					$dimensions = $array->dimensions($env);
					$pointer = $env->arraysMemory()->allocate($this->type, $name, $dimensions);
					$expression->initArray($env, $pointer);
				}
				else
				{
					foreach ($expression->variables() as $variable)
					{
						$env->memory()->allocate($this->type, $variable->name());
					}

					$expression->compile($env);
				}
			}
			else if ($expression instanceof ArrayAccess)
			{
				$name = $expression->variableName();
				$dimensions = $expression->dimensions($env);
				$env->arraysMemory()->allocate($this->type, $name, $dimensions);
			}
			else
			{
				$env->memory()->allocate($this->type, $expression->name());
			}
		}
	}

	public function __toString() : string
	{
		return '';
	}
}