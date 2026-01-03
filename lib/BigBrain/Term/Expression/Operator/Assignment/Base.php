<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\MemoryCellTyped;
use Gordy\Brainfuck\BigBrain\Term\Expression\ArrayVariable;
use Gordy\Brainfuck\BigBrain\Utils;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\MemoryCellArray;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use \Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\HasLexeme;
use Gordy\Brainfuck\BigBrain\Type;

class Base implements Expression
{
	use HasLexeme;

	protected Expression $to;
	protected Expression $value;

	public function __construct(Expression $to, Expression $expr, Lexeme $lexeme)
	{
		$this->to = $to;
		$this->value = $expr;
		$this->lexeme = $lexeme;
	}

	public function resultType(Environment $env) : Type\Type
	{
		return $this->to->resultType($env);
	}

	public function compile(BigBrain\Environment $env) : void
	{
		$env->stream()->blockComment($this);

		//$this->to->set( $this->value);
		// если += или -=, то $this->to->set( $this);

		$resultType = $this->to->resultType($env);

		if ($resultType instanceof Type\Pointer)
		{
			$this->assignArray($env, $resultType);
		}
		else if ($this->to instanceof Expression\Operator\ArrayAccess)
		{
			// todo
		}
		else if ($this->to instanceof Expression\ScalarVariable)
		{
			$memoryCell = $this->to->memoryCell($env);
			$this->assignVariable($env, $memoryCell);
		}
		else
		{
			throw new CompileError('variable expected', $this->lexeme);
		}
	}

	protected function assignArray(Environment $env, Type\Pointer $result) : void
	{
		if ($this->to instanceof Expression\ArrayVariable)
		{
			$startCell = $this->to->memoryCell($env);
			$this->fillArray($env, $startCell);
		}
		else if ($this->to instanceof Expression\Operator\ArrayAccess)
		{
			$indexCell = $env->arraysProcessor()->startCell();
			$this->to->calculateIndex($env, $indexCell);
			$plainArray = $this->prepareArrayValues($env, $result);
			$env->arraysProcessor()->fill($indexCell, $plainArray);
		}
		else
		{
			throw new CompileError('something went wrong', $this->lexeme);
		}
	}

	protected function assignVariable(Environment $env, MemoryCellTyped $memoryCell) : void
	{
		$result = $this->value->resultType($env);

		if ($result instanceof Type\Computable)
		{
			if (!$result->numericCompatible())
			{
				throw new CompileError('numeric type expected', $this->lexeme);
			}

			$env->processor()->unset($memoryCell);

			if ($memoryCell->type() instanceof Type\Boolean)
			{
				$env->processor()->addConstant($memoryCell, $result->getNumeric() !== 0);
			}
			else
			{
				$env->processor()->addConstant($memoryCell, $result->getNumeric());
			}
		}
		else
		{
			$boolCastingNeed = $this->to->resultType($env) instanceof Type\Boolean
				&& !$result instanceof Type\Boolean;

			if ($boolCastingNeed || $this->value->hasVariable($memoryCell->label()))
			{
				$tempResult = $env->processor()->reserve($memoryCell);
				$this->value->compileCalculation($env, $tempResult);

				$env->processor()->unset($memoryCell);
				if ($boolCastingNeed)
				{
					$env->processor()->moveBoolean($tempResult, $memoryCell);
				}
				else
				{
					$env->processor()->moveNumber($tempResult, $memoryCell);
				}
				$env->processor()->release($tempResult);
			}
			else
			{
				$env->processor()->unset($memoryCell);
				$this->value->compileCalculation($env, $memoryCell);
			}
		}
	}

	protected function prepareArrayValues(Environment $env, Type\Pointer $pointer) : array
	{
		$result = $this->value->resultType($env);
		if (!$result instanceof Type\Computable)
		{
			throw new CompileError("can't assign dynamic value to array. only literals supported", $this->lexeme);
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
					$this->lexeme
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
			throw new CompileError(sprintf("can't assign '%s' value to array", $result->type()), $this->lexeme);
		}

		if ($pointer->valueType() instanceof Type\Boolean)
		{
			return Utils\ArraysHelper::toBoolArray($plainArray);
		}
		else
		{
			return $plainArray;
		}
	}

	public function fillArray(Environment $env, MemoryCellArray $pointer) : void
	{
		$plainArray = $this->prepareArrayValues($env, $pointer->type());
		$env->stream()->blockComment($this);
		$env->arraysProcessor()->fill($pointer, $plainArray);
	}

	public function assignArrayIndex(Environment $env) : void
	{
		if (!$this->to->isAssignable())
		{
			throw new CompileError();
		}

		$this->to->resultCell();
		$this->to->assign();



		// if is array => fillArray();
	}

	/** @return Expression\ScalarVariable[] */
	public function variables() : array
	{
		$result = [ $this->to ];
		if ($this->value instanceof self) // a = (b = (c = 0));
		{
			array_push($result, ...$this->value->variables());
		}

		return $result;
	}

//	protected function arrayVariableName() : ArrayVariable
//	{
//		if ($this->value instanceof self)
//		{
//			throw new CompileError('array multiple assignation not supported', $this->value->lexeme());
//		}
//		if ($this->to instanceof Expression\Operator\ArrayAccess)
//		{
//			return $this->to->variable();
//		}
//		if ($this->to instanceof ArrayVariable)
//		{
//			return $this->to;
//		}
//		throw new CompileError('array variable expected', $this->to->lexeme());
//	}

	public function left() : Expression
	{
		return $this->to;
	}

	public function right() : Expression
	{
		return $this->value;
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$this->to->compileCalculation($env, $result);
	}

	public function hasVariable(string $name) : bool
	{
		return $this->value->hasVariable($name);
	}

	public function __toString() : string
	{
		return sprintf('%s = %s', $this->to, $this->value);
	}
}