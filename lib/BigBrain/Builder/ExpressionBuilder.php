<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Parser\LexemeScope;
use Gordy\Brainfuck\BigBrain\Term;

class ExpressionBuilder
{
	public const array PRIORITY = [
		'++', '--',
		'!',
		'*', '/', '%',
		'+', '-', '.',
		'<', '<=', '>', '>=',
		'==', '!=', '===', '!==', '<>',
		'&&', '||',
		'?', // todo ? :
		'=', '+=', '-=', '*=', '/=', '%=', '.=', '&=', '|=',
		',',
	];

	public const array ASSIGNMENT_OPERATORS = [
		'=', '+=', '-=', '*=', '/=', '%=', '.=', '&=', '|=',
	];

	public const array SINGLE_OPERATORS = [
		'++', '--', '!',
	];

	public static function build(LexemeScope $scope) : Term\Expression
	{
		return self::parseExpression($scope);
	}

	protected static function parseExpression(LexemeScope $scope) : Term\Expression
	{
		if (!$scope->hasChildren())
		{
			throw new \Exception('something went wrong');
		}

		if ($scope->isSingle() && !$scope->first() instanceof LexemeScope)
		{
			if ($scope->first()->isLiteral())
			{
				return new Term\Expression\Literal($scope->first());
			}
			else if ($scope->first()->isName())
			{
				return new Term\Expression\Variable($scope->first());
			}
		}
		// a * b + c / (b + d);
		// a ? b : c

		$minPriorityIndex = self::getMinPriorityIndex($scope);

		if ($minPriorityIndex === null)
		{
			return self::parseSpecialExpression($scope);
		}

		$operator = $scope->get($minPriorityIndex);

		if (in_array($operator->value(), self::SINGLE_OPERATORS, true))
		{
			if ($minPriorityIndex === 0)
			{
				// todo
			}
		}
		else
		{
			$left = self::parseExpression($scope->slice(0, $minPriorityIndex));
			$right = self::parseExpression($scope->slice($minPriorityIndex + 1));

			if (in_array($operator->value(), self::ASSIGNMENT_OPERATORS, true))
			{
				return self::parseAssignment($left, $right, $operator);
			}
			else
			{
				return self::parseBinaryOperator($left, $right, $operator);
			}
		}

		throw new SyntaxError('cant parse expression', $scope);
	}

	protected static function parseSpecialExpression(LexemeScope $parts) : Term\Expression
	{
		// todo fn(a + b)[i]
		// todo (int)
		// todo a[i]
		if (count($parts->children()) === 1)
		{
			$first = $parts->first();
			if ($first instanceof LexemeScope)
			{
				$scopeType = $first->value();

				if ($scopeType === '(')
				{
					return self::parseExpression($first);
				}
				else
				{
					throw new SyntaxError('not supported yet', $parts->first());
				}
			}
			else
			{
				return new Term\Expression\Variable($parts->first());
			}
		}
		else
		{
			throw new SyntaxError('not supported yet', $parts->first());
		}
	}

	protected static function parseAssignment(Term\Expression $left, Term\Expression $right, Lexeme $operator)
	{
		if (!$left instanceof Term\Expression\Variable)
		{
			throw new SyntaxError('left operand must be a variable', $operator);
		}

		return match ($operator->value()) {
			'=' => new Term\Expression\Assignment\Base($left, $right, $operator),
			default => throw new SyntaxError('not supported yet', $operator),
		};
	}

	protected static function parseBinaryOperator(Term\Expression $left, Term\Expression $right, Lexeme $operator)
	{
		return match($operator->value()) {
			'+' => new Term\Expression\Operator\Arithmetic\Addition($left, $right, $operator),
			'-' => new Term\Expression\Operator\Arithmetic\Subtraction($left, $right, $operator),
			',' => new Term\Expression\Operator\Comma($left, $right, $operator),
			default => throw new SyntaxError('unknown operator', $operator),
		};
	}

	protected static function getMinPriorityIndex(LexemeScope $parts) : ?int
	{
		$priorityMap = array_flip(self::PRIORITY);

		$minPriority = 0;
		$minPriorityIndex = null;
		foreach ($parts->children() as $key => $part)
		{
			if ($part instanceof LexemeScope) { continue; }
			$value = $part->value();

			if (isset($priorityMap[$value]) && $priorityMap[$value] > $minPriority)
			{
				$minPriority = $priorityMap[$value];
				$minPriorityIndex = $key;
			}
		}

		return $minPriorityIndex;
	}
}