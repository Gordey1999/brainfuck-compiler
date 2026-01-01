<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\MemoryCellTyped;
use Gordy\Brainfuck\BigBrain\Utils;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\MemoryCellPointer;
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

		if ($this->to instanceof Expression\Variable)
		{
			$memoryCell = $this->to->memoryCell($env);

			if ($memoryCell instanceof MemoryCellPointer)
			{
				$this->fillArray($env, $memoryCell);
			}
			else
			{
				$this->assignVariable($env, $memoryCell);
			}
		}
		else if ($this->to instanceof Expression\Operator\ArrayAccess)
		{
			echo 'not ready';die;
		}
		else
		{
			throw new CompileError('variable expected', $this->lexeme);
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

	public function fillArray(Environment $env, MemoryCellPointer $pointer) : void
	{
		$env->stream()->blockComment($this);

		$result = $this->value->resultType($env);
		if (!$result instanceof Type\Computable)
		{
			throw new CompileError('wrong assignment value', $this->lexeme);
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
						"value dimensions not compatible with '%s' dimensions: [%s] != [%s]",
						$pointer->label(),
						implode(', ', $pointerSizes),
						implode(', ', $valueSizes)
					),
					$this->lexeme
				);
			}

			$plainArray = Utils\ArraysHelper::plainArray($value, $pointerSizes);
			if ($pointer->type() instanceof Type\Boolean)
			{
				$plainArray = Utils\ArraysHelper::toBoolArray($plainArray);
			}
			$env->arraysProcessor()->fill($pointer, $plainArray);
		}
		else if ($result->numericCompatible())
		{
			$value = $result->getNumeric();
			$plainArray = array_fill(0, $pointer->plainSize(), $value);
			if ($pointer->type() instanceof Type\Boolean)
			{
				$plainArray = Utils\ArraysHelper::toBoolArray($plainArray);
			}
			$env->arraysProcessor()->fill($pointer, $plainArray);
		}
		else
		{
			throw new CompileError('wrong assignment value', $this->lexeme);
		}
	}

	/** @return Expression\Variable[] */
	public function variables() : array
	{
		$result = [ $this->to ];
		if ($this->value instanceof self) // a = (b = (c = 0));
		{
			array_push($result, ...$this->value->variables());
		}

		return $result;
	}

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
		$this->compile($env);

		if ($this->to instanceof Expression\Variable)
		{
			$memoryCell = $this->to->memoryCell($env);

			if ($memoryCell instanceof MemoryCellPointer)
			{
				throw new CompileError('not supported', $this->lexeme);
			}

			$env->processor()->copyNumber($memoryCell, $result);
		}
		else if ($this->to instanceof Expression\Operator\ArrayAccess)
		{
			echo 'not ready';die; // todo
		}
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