<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;

use Gordy\Brainfuck\BigBrain;
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
	protected Expression $expression;

	public function __construct(Expression $to, Expression $expr, Lexeme $lexeme)
	{
		$this->to = $to;
		$this->expression = $expr;
		$this->lexeme = $lexeme;
	}

	public function resultType(Environment $env) : Type\Type
	{
		throw new \Exception('Not implemented');
	}

	public function compile(BigBrain\Environment $env) : void
	{
		$env->stream()->blockComment($this);

		$variables = $this->variables();

		$value = $this->value();
		$resultType = $value->resultType($env);

		if ($resultType instanceof Type\Computable)
		{
			$this->assignComputed($env, $variables, $resultType);
		}
		else
		{
			$this->assignCalculate($env, $variables, $resultType);
		}
	}

	/** @param Expression\Variable[] $variables */
	protected function assignComputed(Environment $env, array $variables, Type\Computable $result) : void
	{
		if (!$result->numericCompatible())
		{
			throw new CompileError('numeric type expected', $this->lexeme);
		}

		foreach ($variables as $variable)
		{
			$cell = $variable->memoryCell($env);
			$env->processor()->unset($cell);

			if ($variable->resultType($env) instanceof Type\Boolean)
			{
				$env->processor()->addConstant($cell, $result->getNumeric() !== 0);
			}
			else
			{
				$env->processor()->addConstant($cell, $result->getNumeric());
			}
		}
	}

	/** @param Expression\Variable[] $variables */
	protected function assignCalculate(Environment $env, array $variables, Type\Type $result) : void
	{
		$value = $this->value();
		$last = array_shift($variables);

		$variableCells = array_map(static function ($variable) use ($env) {
			return $variable->memoryCell($env);
		}, $variables);

		$boolCastingNeed = !$result instanceof Type\Boolean
			&& $last->resultType($env) instanceof Type\Boolean;

		if ($boolCastingNeed || $value->hasVariable($last->name()->value()))
		{
			$cell = $last->memoryCell($env);
			$tempResult = $env->processor()->reserve($cell);

			$value->compileCalculation($env, $tempResult);

			$env->processor()->unsetSeveral($cell, ...$variableCells);
			$env->processor()->move($tempResult, $this->buildMoveAddresses($env, $last, ...$variables));
			$env->processor()->release($tempResult);
		}
		else
		{
			$cell = $last->memoryCell($env);
			$env->processor()->unset($cell);

			$value->compileCalculation($env, $cell);

			$env->processor()->unsetSeveral(...$variableCells);
			$env->processor()->copy($cell, $this->buildMoveAddresses($env, ...$variables));
		}
	}

	/** @param Expression\Variable[] $variables */
	protected function buildMoveAddresses(Environment $env, ...$variables) : array
	{
		$result = [];

		foreach ($variables as $variable)
		{
			if ($variable->resultType($env) instanceof Type\Boolean)
			{
				$result[] = [
					$variable->memoryCell($env),
					$env->processor()::BOOLEAN,
				];
			}
			else
			{
				$result[] = [
					$variable->memoryCell($env),
					$env->processor()::NUMBER,
				];
			}
		}

		return $result;
	}

	public function initArray(Environment $env, MemoryCellPointer $pointer) : void
	{
		$env->stream()->blockComment($this);

		$result = $this->expression->resultType($env);
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
	}

	/** @return Expression\Variable[] */
	public function variables() : array
	{
		$result = [];
		if ($this->expression instanceof self) // a = b = c = 0;
		{
			$result = $this->expression->variables();
		}
		$result[] = $this->to;

		return $result;
	}

	public function left() : Expression
	{
		return $this->to;
	}

	public function value() : Expression
	{
		if ($this->expression instanceof self) // a = b = c = 0;
		{
			return $this->expression->value();
		}
		return $this->expression;
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		throw new \Exception('not implemented');
	}

	public function hasVariable(string $name) : bool
	{
		return false;
	}

	public function __toString() : string
	{
		return sprintf('%s = %s', $this->to, $this->expression);
	}
}