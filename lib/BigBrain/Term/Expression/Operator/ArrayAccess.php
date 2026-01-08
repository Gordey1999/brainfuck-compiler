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
			throw new CompileError('array size must be constant', $this->index->lexeme());
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
		$type = $this->resultType($env);
		if ($type instanceof Type\Scalar)
		{
			$startCell = $env->arraysProcessor()->startCell();
			$this->calculateIndex($env, $startCell);
			$carry = $env->arraysProcessor()->get($startCell);
			$env->processor()->move($carry, $result);
		}
		else
		{
			throw new CompileError('not expected', $this->variable()->lexeme());
		}
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
			$this->assignScalar($env, $value, $modifier);
		}
		else
		{
			throw new CompileError('not expected', $this->lexeme);
		}
	}

	protected function assignScalar(Environment $env, Expression $value, string $modifier) : void
	{
		$result = $value->resultType($env);

		if ($result instanceof Type\Computable)
		{
			$this->assignComputed($env, $result, $value, $modifier);
		}
		else if ($result instanceof Type\Scalar)
		{
			$this->assignVariable($env, $value, $modifier);
		}
		else
		{
			throw new CompileError('scalar value expected', $value->lexeme());
		}
	}

	protected function assignComputed(Environment $env, Type\Computable $result, Expression $value, string $modifier) : void
	{
		$this->checkAssignType($env, $value, $modifier);
		$isBool = $this->resultType($env) instanceof Type\Boolean;

		$this->calculateAssignIndex($env, $modifier);

		if (!$result->numericCompatible())
		{
			throw new CompileError('numeric type expected', $value->lexeme());
		}

		$numericValue = $result->getNumeric();

		$startCell = $env->arraysProcessor()->startCell();
		if ($modifier === self::ASSIGN_SET)
		{
			$env->arraysProcessor()->setConstant($startCell, $isBool ? $numericValue !== 0 : $numericValue);
		}
		else if ($modifier === self::ASSIGN_ADD)
		{
			$env->arraysProcessor()->addConstant($startCell, $numericValue);
		}
		else if ($modifier === self::ASSIGN_SUB)
		{
			$env->arraysProcessor()->addConstant($startCell, -$numericValue);
		}
		else
		{
			$dummyCell = $env->arraysProcessor()->dummyCell();
			$carryCell = $env->arraysProcessor()->carryCell();
			$valueCell = $env->arraysProcessor()->get($startCell);

			if ($modifier === self::ASSIGN_MULTIPLY)
			{
				$env->processor()->multiplyByConstant($valueCell, $numericValue, $carryCell);
			}
			else if ($modifier === self::ASSIGN_DIVIDE)
			{
				Expression\Calculation\Division::divideByConstant($env, $valueCell, $numericValue, $carryCell);
			}
			else if ($modifier === self::ASSIGN_MODULO)
			{
				Expression\Calculation\Modulo::divideByConstant($env, $valueCell, $numericValue, $carryCell);
			}
			else
			{
				throw new CompileError('undefined modifier', $this->lexeme);
			}
			$env->arraysProcessor()->set($dummyCell);
		}
	}

	protected function assignVariable(Environment $env, Expression $value, string $modifier) : void
	{
		$this->checkAssignType($env, $value, $modifier);
		$castBool = $this->resultType($env) instanceof Type\Boolean
			&& !$value->resultType($env) instanceof Type\Boolean;

		$this->calculateAssignIndex($env, $modifier);

		$startCell = $env->arraysProcessor()->startCell();
		$carryCell = $env->arraysProcessor()->carryCell();

		if ($modifier === self::ASSIGN_SET)
		{
			if ($castBool)
			{
				$tempResult = $env->processor()->reserve($startCell);
				$value->compileCalculation($env, $tempResult);
				$env->processor()->moveBoolean($tempResult, $carryCell);
				$env->processor()->release($tempResult);
				$env->arraysProcessor()->set($startCell);
			}
			else
			{
				$value->compileCalculation($env, $carryCell);
				$env->arraysProcessor()->set($startCell);
			}
		}
		else if ($modifier === self::ASSIGN_ADD)
		{
			$value->compileCalculation($env, $carryCell);
			$env->arraysProcessor()->add($startCell);
		}
		else if ($modifier === self::ASSIGN_SUB)
		{
			$value->compileCalculation($env, $carryCell);
			$env->arraysProcessor()->sub($startCell);
		}
		else
		{
			$tempResult = $env->processor()->reserve($startCell);
			$value->compileCalculation($env, $tempResult);
			$dummyCell = $env->arraysProcessor()->dummyCell();
			$valueCell = $env->arraysProcessor()->get($startCell);

			if ($modifier === self::ASSIGN_MULTIPLY)
			{
				$env->processor()->multiply($valueCell, $tempResult, $carryCell);
			}
			else if ($modifier === self::ASSIGN_DIVIDE)
			{
				Expression\Calculation\Division::divide($env, $valueCell, $tempResult, $carryCell);
			}
			else if ($modifier === self::ASSIGN_MODULO)
			{
				Expression\Calculation\Modulo::divide($env, $valueCell, $tempResult, $carryCell);
			}
			else
			{
				throw new CompileError('undefined modifier', $this->lexeme);
			}
			$env->arraysProcessor()->set($dummyCell);
			$env->processor()->release($tempResult);
		}
	}

	protected function calculateAssignIndex(Environment $env, string $modifier) : void
	{
		$startCell = $env->arraysProcessor()->startCell();
		$dummyCell = $env->arraysProcessor()->dummyCell();

		if ($this->isSimpleModifier($modifier))
		{
			$this->calculateIndex($env, $startCell);
		}
		else
		{
			$temp = $env->processor()->reserve($startCell);
			$this->calculateIndex($env, $temp);
			$env->processor()->move($temp, $startCell, $dummyCell);
			$env->processor()->release($temp);
		}
	}

	protected function isSimpleModifier(string $modifier) : bool
	{
		return in_array($modifier, [self::ASSIGN_SET, self::ASSIGN_ADD, self::ASSIGN_SUB], true);
	}

	protected function checkAssignType(Environment $env, Expression $value, string $modifier) : void
	{
		$isBool = $this->resultType($env) instanceof Type\Boolean;
		$isArithmetic = in_array($modifier, self::ASSIGN_ARITHMETIC);

		if ($isBool && $isArithmetic)
		{
			throw new CompileError("Why? It's bool variable. It's stupid. I won't do it.", $value->lexeme());
		}
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