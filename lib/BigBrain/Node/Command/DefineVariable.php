<?php

namespace Gordy\Brainfuck\BigBrain\Node\Command;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Node;
use Gordy\Brainfuck\BigBrain\Node\Expression;
use Gordy\Brainfuck\BigBrain\Node\Expression\ArrayVariable;
use Gordy\Brainfuck\BigBrain\Node\Expression\ScalarVariable;
use Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Assignment;
use Gordy\Brainfuck\BigBrain\Node\Expression\Operator\ArrayAccess;
use Gordy\Brainfuck\BigBrain\Utils;
use Gordy\Brainfuck\BigBrain\Type;

class DefineVariable implements Node\Command
{
	use Node\HasToken;

	private BigBrain\Type\BaseType $type;

	/** @var Expression[] $variables */
	private array $variables;

	public function __construct(BigBrain\Type\BaseType $type, Expression $expr, Token $token)
	{
		$this->type = $type;
		$this->variables = $this->getVariableList($expr);
		$this->token = $token;
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
			if (!$var instanceof ScalarVariable
				&& !$var instanceof ArrayVariable
				&& !$var instanceof Assignment\Base
				&& !$var instanceof ArrayAccess)
			{
				throw new SyntaxError('variable name expected', $var->token());
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
				$this->allocateAndInitialize($env, $expression);
			}
			else
			{
				$this->allocate($env, $expression);
			}
		}
	}

	protected function allocate(Environment $env, Expression $variable) : void
	{
		if ($variable instanceof ArrayAccess)
		{
			$name = $variable->variable()->name();
			$dimensions = $variable->dimensions($env);
			if (Utils\ArraysHelper::hasNull($dimensions))
			{
				throw new CompileError('array size expected', $variable->token());
			}

			$env->arraysMemory()->allocate($this->type, $name, $dimensions);
		}
		else if ($variable instanceof ScalarVariable)
		{
			$env->memory()->allocate($this->type, $variable->name());
		}
		else
		{
			throw new CompileError('variable name expected', $variable->token());
		}
	}

	protected function allocateAndInitialize(Environment $env, Assignment\Base $assignment) : void
	{
		if ($assignment->left() instanceof ArrayAccess)
		{
			/** @var ArrayAccess $array */
			$array = $assignment->left();
			$name = $array->variable()->name();
			$dimensions = $array->dimensions($env);
			if (Utils\ArraysHelper::hasNull($dimensions))
			{
				$dimensions = $this->calculateArrayDimensions($env, $assignment);
			}

			$pointer = $env->arraysMemory()->allocate($this->type, $name, $dimensions);

			$env->stream()->blockComment($assignment);
			$array->variable()->fillArray($env, $pointer, $assignment->right());
		}
		else if ($assignment->left() instanceof ScalarVariable)
		{
			foreach ($assignment->variables() as $variable)
			{
				$env->memory()->allocate($this->type, $variable->name());
			}

			$assignment->compile($env);
		}
		else
		{
			throw new CompileError('variable name expected', $assignment->left()->token());
		}
	}

	protected function calculateArrayDimensions(Environment $env, Assignment\Base $assignment) : array
	{
		/** @var ArrayAccess $array */
		$array = $assignment->left();
		$expression = $assignment->right();
		$result = $expression->resultType($env);

		if (!$result instanceof Type\Computable)
		{
			throw new CompileError('wrong assignment value', $assignment->token());
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
						$array->variable($env)->name()->value(),
						implode(', ', array_map(function ($item) {
							return $item === null ? 'n': $item;
							}, $targetSizes)),
						implode(', ', $valueSizes)
					),
					$assignment->token()
				);
			}
			return Utils\ArraysHelper::dimensionsUnion($valueSizes, $targetSizes);
		}
		else if ($result->numericCompatible())
		{
			return [ 1 ];
		}
		throw new CompileError('wrong assignment value', $assignment->token());
	}

	public function __toString() : string
	{
		return sprintf("%s %s", $this->type, implode(', ', $this->variables));
	}
}