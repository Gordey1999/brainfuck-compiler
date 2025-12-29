<?php

namespace Gordy\Brainfuck\BigBrain\Term\Command;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Term\Expression;

class DefineVariable implements Term\Command
{
	use Term\HasLexeme;

	private BigBrain\Type\BaseType $type;

	/** @var Expression\Operator\Assignment\Base[]|Expression\Variable[] */
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
			if (!$var instanceof Expression\Variable && !$var instanceof Expression\Operator\Assignment\Base)
			{
				throw new SyntaxError('assignment or variable name expected', $var->lexeme());
			}
		}

		return $varList;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		foreach ($this->variables as $expression)
		{
			if ($expression instanceof Expression\Operator\Assignment\Base)
			{
				foreach ($expression->variables() as $variable)
				{
					$env->memory()->allocate($this->type, $variable->name());
				}

				$expression->compile($env);
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