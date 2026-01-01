<?php

namespace Gordy\Brainfuck\BigBrain\Term\Command;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\Expression\Variable;
use Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;
use Gordy\Brainfuck\BigBrain\Term\Expression\Operator\ArrayAccess;
use Gordy\Brainfuck\BigBrain\Utils;
use Gordy\Brainfuck\BigBrain\Type;

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
					if (Utils\ArraysHelper::hasNull($dimensions))
					{
						$dimensions = $this->calculateArrayDimensions($env, $array, $expression);
					}

					$pointer = $env->arraysMemory()->allocate($this->type, $name, $dimensions);
					$expression->fillArray($env, $pointer);
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
				if (Utils\ArraysHelper::hasNull($dimensions))
				{
					throw new CompileError('array size expected', $expression->lexeme());
				}
				$env->arraysMemory()->allocate($this->type, $name, $dimensions);
			}
			else
			{
				$env->memory()->allocate($this->type, $expression->name());
			}
		}
	}

	protected function calculateArrayDimensions(Environment $env, ArrayAccess $array, Assignment\Base $assignment) : array
	{
		$expression = $assignment->right();
		$result = $expression->resultType($env);

		if (!$result instanceof Type\Computable)
		{
			throw new CompileError('wrong assignment value', $assignment->lexeme());
		}
		if ($result->arrayCompatible())
		{
			$value = $result->getArray();

			$valueSizes = Utils\ArraysHelper::dimensions($value);
			$targetSizes = $array->dimensions($env);

			if (!Utils\ArraysHelper::dimensionsCompatible($valueSizes, $targetSizes))
			{
				throw new CompileError(
					sprintf(
						"value dimensions not compatible with '%s' dimensions: [%s] != [%s]",
						$array->variableName()->value(),
						implode(', ', $targetSizes),
						implode(', ', $valueSizes)
					),
					$assignment->lexeme()
				);
			}
			return Utils\ArraysHelper::dimensionsUnion($valueSizes, $targetSizes);
		}
		else if ($result->numericCompatible())
		{
			return [ 1 ];
		}
		throw new CompileError('wrong assignment value', $assignment->lexeme());
	}

	public function __toString() : string
	{
		return '';
	}
}