<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\MemoryCellArray;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Utils;

class ArrayVariable implements Expression, Assignable
{
	use BigBrain\Term\HasToken;

	public function __construct(Token $name)
	{
		$this->token = $name;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		// do nothing
	}

	public function name() : Token
	{
		return $this->token;
	}

	public function memoryCell(Environment $env) : MemoryCellArray
	{
		return $env->arraysMemory()->get($this->name());
	}

	public function resultType(Environment $env) : Type\Pointer
	{
		return $this->memoryCell($env)->type();
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		throw new CompileError('scalar type expected', $this->name());
	}

	public function assign(Environment $env, Expression $value, string $modifier) : void
	{
		if ($modifier !== self::ASSIGN_SET)
		{
			throw new CompileError('only "=" operator supported to fill array', $value->token());
		}
		$startCell = $this->memoryCell($env);
		$this->fillArray($env, $startCell, $value);
	}

	public function fillArray(Environment $env, MemoryCell $pointer, Expression $value) : void
	{
		$plainArray = $this->prepareArrayValues($env, $pointer->type(), $value);
		$env->arraysProcessor()->fill($pointer, $plainArray);
	}

	public function prepareArrayValues(Environment $env, Type\Pointer $pointer, Expression $value) : array
	{
		$result = $value->resultType($env);
		if (!$result instanceof Type\Computable)
		{
			throw new CompileError("can't assign dynamic value to array. only literals supported", $this->token());
		}
		if ($result->arrayCompatible())
		{
			$value = $result->getArray();
			$pointerSizes = $pointer->sizes();

			$valueSizes = Utils\ArraysHelper::dimensions($value);

			if (!Utils\ArraysHelper::dimensionsCompatible($valueSizes, $pointerSizes))
			{
				throw new CompileError(
					sprintf(
						"array dimensions not compatible with value dimensions: [%s] != [%s]",
						implode(', ', $pointerSizes),
						implode(', ', $valueSizes)
					),
					$this->token
				);
			}

			$plainArray = Utils\ArraysHelper::plainArray($value, $pointerSizes);
		}
		else if ($result->numericCompatible())
		{
			$value = $result->getNumeric();
			$plainArray = array_fill(0, $pointer->plainSize(), $value);
		}
		else
		{
			throw new CompileError(sprintf("can't assign '%s' value to array", $result->type()), $this->token);
		}

		if ($pointer->baseType() instanceof Type\Boolean)
		{
			return Utils\ArraysHelper::toBoolArray($plainArray);
		}
		else
		{
			return $plainArray;
		}
	}

	public function hasVariable(string $name) : bool
	{
		return false;
	}

	public function __toString() : string
	{
		return $this->token()->value();
	}
}