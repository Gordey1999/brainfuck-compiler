<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\MemoryCellArray;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use \Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\HasLexeme;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Utils;

class ArrayAccess implements Expression, Expression\Assignable
{
	use HasLexeme;

	protected Expression $to;
	protected Expression $index;

	public function __construct(Expression $to, Expression $index, Lexeme $lexeme)
	{
		$this->to = $to;
		$this->index = $index;
		$this->lexeme = $lexeme;
	}

	public function variable() : Expression\ArrayVariable
	{
		if ($this->to instanceof Expression\ArrayVariable)
		{
			return $this->to;
		}
		if ($this->to instanceof self)
		{
			return $this->to->variable();
		}

		throw new SyntaxError('array name expected', $this->to->lexeme());
	}

	public function dimensions(Environment $env) : array
	{
		$resultType = $this->index->resultType($env);

		if (!$resultType instanceof Type\Computable)
		{
			throw new CompileError('dynamic expressions not allowed here', $this->index->lexeme());
		}
		if (!$resultType->numericNullableCompatible())
		{
			throw new CompileError('numeric expected', $this->index->lexeme());
		}

		if ($this->to instanceof self)
		{
			return array_merge(
				$this->to->dimensions($env),
				[ $resultType->getNumericNullable() ]
			);
		}
		return [$resultType->getNumericNullable()];
	}

	public function resultType(Environment $env) : Type\BaseType
	{
		$result = $this->to->resultType($env);

		if ($result instanceof Type\Pointer)
		{
			return $result->valueType();
		}
		else
		{
			throw new CompileError('array operand expected, scalar passed', $this->to->lexeme());
		}
	}

	public function compile(BigBrain\Environment $env) : void
	{
		// do nothing
		// todo expression may have $i++
		// todo compile children until mutation expression
	}

	protected function startCell($env) : MemoryCellArray
	{
		$cell = $this->variable($env)->memoryCell($env);
		if (!$cell instanceof MemoryCellArray)
		{
			throw new SyntaxError('array expected', $this->to->lexeme());
		}
		return $cell;
	}

	/** @return Expression[] */
	protected function indexes(Environment $env) : array
	{
		$result = [];
		if ($this->to instanceof self)
		{
			$result = $this->to->indexes($env);
		}
		$result[] = $this->index;

		$sizes = $this->startCell($env)->type()->sizes();
		if (count($result) > count($sizes))
		{
			throw new CompileError(
				sprintf('wrong index count. Array has only %s dimensions', count($sizes)),
				$this->lexeme()
			);
		}

		return $result;
	}

	public function calculateIndex(Environment $env, MemoryCell $result) : void
	{
		$sizes = $this->startCell($env)->type()->sizes();
		$multipliers = Utils\ArraysHelper::indexMultipliers($sizes);
		$indexes = $this->indexes($env);

		$computedIndex = $this->startCell($env)->startIndex();
		foreach ($indexes as $key => $index)
		{
			$indexResult = $index->resultType($env);
			if ($indexResult instanceof Type\Computable)
			{
				if (!$indexResult->numericCompatible())
				{
					throw new CompileError('numeric index expected', $this->lexeme());
				}
				$computedIndex += $indexResult->getNumeric() * $multipliers[$key];
			}
			else
			{
				if ($multipliers[$key] === 1)
				{
					$index->compileCalculation($env, $result);
				}
				else
				{
					$temp = $env->processor()->reserve($result);
					$index->compileCalculation($env, $temp);
					$env->processor()->multiplyByConstant($temp, $multipliers[$key], $result);
					$env->processor()->release($temp);
				}
			}
		}
		if  ($computedIndex > 0)
		{
			$env->processor()->addConstant($result, $computedIndex);
		}
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$startCell = $env->arraysProcessor()->startCell();
		$this->calculateIndex($env, $startCell);
		$carry = $env->arraysProcessor()->get($startCell);
		$env->processor()->moveNumber($carry, $result);
	}

	public function assign(Environment $env, Expression $value, string $modifier) : void
	{
		$selfType = $this->resultType($env);

		if ($selfType instanceof Type\Pointer)
		{
			if ($modifier !== self::ASSIGN_SET)
			{
				throw new CompileError('only "=" operator supported to fill array', $value->lexeme());
			}
			$indexCell = $env->arraysProcessor()->startCell();
			$this->calculateIndex($env, $indexCell);
			$plainArray = $this->variable()->prepareArrayValues($env, $selfType, $value);
			$env->arraysProcessor()->fill($indexCell, $plainArray);
		}
		else if ($selfType instanceof Type\Scalar)
		{
			// todo
			//$this->assignVariable($env, $resultType, $value, $modifier);
		}
		else
		{
			throw new CompileError('not expected', $this->lexeme);
		}
	}

	protected function assignVariable(Environment $env, Type\Scalar $result, Expression $value, string $modifier) : void
	{

	}

	public function hasVariable(string $name) : bool
	{
		return $this->index->hasVariable($name) || $this->to->hasVariable($name);
	}

	public function __toString() : string
	{
		return sprintf('%s[%s]', $this->to, $this->index);
	}
}