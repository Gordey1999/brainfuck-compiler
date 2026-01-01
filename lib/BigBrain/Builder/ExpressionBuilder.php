<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Parser\LexemeScope;
use Gordy\Brainfuck\BigBrain\Term;

class ExpressionBuilder
{
	public const array PRIORITY = [
		[ '++', '--' ],
		[ '!' ],
		[ '*', '/', '%' ],
		[ '+', '-', '.' ],
		[ '<', '<=', '>', '>=' ],
		[ '==', '!=', '===', '!==', '<>' ],
		[ '&&', '||' ],
		[ '?' ], // todo ? :
		[ '=', '+=', '-=', '*=', '/=', '%=', '.=', '&=', '|=' ],
		[ ',' ],
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
		if ($scope->empty())
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

			return self::parseBinaryOperator($left, $right, $operator);
		}

		throw new SyntaxError('cant parse expression', $scope);
	}

	protected static function parseSpecialExpression(LexemeScope $scope) : Term\Expression
	{
		// todo fn(a + b)[i]
		// todo (int)
		// todo a[i]
		if ($scope->count() === 1)
		{
			$first = $scope->first();
			if ($first instanceof LexemeScope)
			{
				$scopeType = $first->value();

				if ($scopeType === '(')
				{
					return self::parseExpression($first);
				}
				else if ($scopeType === '[')
				{
					return new Term\Expression\ArrayScope(
						self::parseExpression($first),
						$first,
					);
				}
				else
				{
					throw new SyntaxError('not supported yet', $scope->first());
				}
			}
			else
			{
				return new Term\Expression\Variable($scope->first());
			}
		}
		else if ($scope->first()->isName()) // fn(), a[][]
		{
			return self::parseAccess($scope);
		}
		else
		{
			throw new SyntaxError('not supported yet', $scope->first());
		}
	}

	protected static function parseAccess(LexemeScope $scope) : Term\Expression
	{
		$last = $scope->last();
		if ($last instanceof LexemeScope)
		{
			if ($last->value() === '[')
			{
				return new Term\Expression\Operator\ArrayAccess(
					self::parseAccess($scope->slice(0, -1)),
					$last->empty() ? new Term\Expression\None() : self::parseExpression($last),
					$last
				);
			}
			else
			{
				throw new SyntaxError('not supported yet(parseAccess)', $scope->last());
			}
		}
		else
		{
			return self::parseExpression($scope);
		}
	}

	protected static function parseBinaryOperator(Term\Expression $left, Term\Expression $right, Lexeme $operator)
	{
		return match($operator->value()) {
			'=' => new Term\Expression\Operator\Assignment\Base($left, $right, $operator),
			'+' => new Term\Expression\Operator\Arithmetic\Addition($left, $right, $operator),
			'-' => new Term\Expression\Operator\Arithmetic\Subtraction($left, $right, $operator),
			'*' => new Term\Expression\Operator\Arithmetic\Multiplication($left, $right, $operator),
			'/' => new Term\Expression\Operator\Arithmetic\Division($left, $right, $operator),
			'%' => new Term\Expression\Operator\Arithmetic\DivisionByModulo($left, $right, $operator),
			',' => new Term\Expression\Operator\Comma($left, $right, $operator),
			default => throw new SyntaxError('unknown operator', $operator),
		};
	}

	protected static function getMinPriorityIndex(LexemeScope $parts) : ?int
	{
		$minPriority = 0;
		$minPriorityIndex = null;
		foreach ($parts->children() as $key => $part)
		{
			if ($part instanceof LexemeScope) { continue; }
			$value = $part->value();

			foreach (self::PRIORITY as $priority => $operators)
			{
				if (in_array($value, $operators, true) && $priority >= $minPriority)
				{
					if ($value === '=' && $priority === $minPriority) { continue; }
					$minPriority = $priority;
					$minPriorityIndex = $key;
				}
			}
		}

		return $minPriorityIndex;
	}
}