<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

use Gordy\Brainfuck\BigBrain\Exception\ParseError;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Parser\LexemeScope;
use Gordy\Brainfuck\BigBrain\Term;

class ExpressionBuilder
{
	public const array PRIORITY = [
		[ '[', '(', '.' ],
		[ '++', '--' ],
		[ '!' ],
		[ '*', '/', '%' ],
		[ '+', '-' ],
		[ '<', '<=', '>', '>=' ],
		[ '==', '!=', '===', '!==', '<>' ],
		[ '&&', '||' ],
		[ '?' ], // todo ? :
		[ '=', '+=', '-=', '*=', '/=', '%=', '.=', '&=', '|=' ],
		[ ',' ],
	];

	public const array SINGLE_OPERATORS = [
		'++', '--', '!', '[', '(', '.'
	];

	public const array REVERSE_PRIORITY = [
		'=', '+=', '-=', '*=', '/=', '%=', '.=', '&=', '|=',
	];

	protected Names $names;

	public function __construct(Names $names)
	{
		$this->names = $names;
	}

	public function build(LexemeScope $scope) : Term\Expression
	{
		return $this->parseExpression($scope);
	}

	protected function parseExpression(LexemeScope $scope) : Term\Expression
	{
		if ($scope->empty())
		{
			throw new SyntaxError('expression expected', $scope);
			// todo появляется, если в массиве поставить лишнюю переменную
		}

		if ($scope->isSingle()) // a, 5, [1,2,3], (a + b)
		{
			return $this->parseSingeExpression($scope->first());
		}

		$minPriorityIndex = $this->getMinPriorityIndex($scope);
		if ($minPriorityIndex === null)
		{
			throw new ParseError("can't parse expression", $scope);
		}

		$operator = $scope->get($minPriorityIndex);

		if (in_array($operator->value(), self::SINGLE_OPERATORS, true))
		{
			if ($operator->value() === '[' || $operator->value() === '(') // a[b], a(b)
			{
				return $this->parseAccess($scope);
			}
			else if ($minPriorityIndex === 0) // ++a, --a, !a
			{
				$operand = $this->parseExpression($scope->slice(1));
				return $this->parseSingleOperatorBefore($operand, $operator);
			}
			else if ($minPriorityIndex === $scope->count() - 1) // a++, a--,
			{
				$operand = $this->parseExpression($scope->slice(0, -1));
				return $this->parseSingleOperatorAfter($operand, $operator);
			}
			else
			{
				throw new ParseError("can't parse expression", $scope);
			}
		}
		else
		{
			$left = $this->parseExpression($scope->slice(0, $minPriorityIndex));
			$right = $this->parseExpression($scope->slice($minPriorityIndex + 1));

			return $this->parseBinaryOperator($left, $right, $operator);
		}
	}

	protected function parseSingeExpression(Lexeme $lexeme) : Term\Expression
	{
		if ($lexeme->isLiteral()) // 5
		{
			return new Term\Expression\Literal($lexeme);
		}
		else if ($lexeme->isName()) // a
		{
			if ($this->names->isVariable($lexeme->value()))
			{
				return new Term\Expression\ScalarVariable($lexeme);
			}
			else if ($this->names->isArray($lexeme->value()))
			{
				return new Term\Expression\ArrayVariable($lexeme);
			}
			else
			{
				throw new ParseError("can't parse expression", $lexeme);
			}
		}
		else if ($lexeme instanceof LexemeScope && $lexeme->value() === '(') // (a + b)
		{
			return $this->parseExpression($lexeme);
		}
		else if ($lexeme instanceof LexemeScope && $lexeme->value() === '[') // [1, 2, 3]
		{
			return new Term\Expression\ArrayScope(
				$this->parseExpression($lexeme),
				$lexeme,
			);
		}
		else
		{
			throw new ParseError("can't parse expression", $lexeme);
		}
	}

	protected function parseSingleOperatorBefore(Term\Expression $operand, Lexeme $operator) : Term\Expression
	{
		return match ($operator->value()) {
			'++' => new Term\Expression\Operator\Arithmetic\Increment($operand, $operator),
			default => throw new ParseError("can't parse expression", $operator),
		};
	}

	protected function parseSingleOperatorAfter(Term\Expression $operand, Lexeme $operator) : Term\Expression
	{
		throw new ParseError("notReady", $operator);
	}

	protected function parseAccess(LexemeScope $scope) : Term\Expression
	{
		$last = $scope->last();
		if ($last instanceof LexemeScope)
		{
			if ($last->value() === '[')
			{
				return new Term\Expression\Operator\ArrayAccess(
					$this->parseAccess($scope->slice(0, -1)),
					$last->empty() ? new Term\Expression\None() : $this->parseExpression($last),
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
			if ($scope->first()->isName())
			{
				$this->names->remember($scope->first()->value(), $this->names::ARRAY);
			}
			return $this->parseExpression($scope);
		}
	}

	protected function parseBinaryOperator(Term\Expression $left, Term\Expression $right, Lexeme $operator) : Term\Expression
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

	protected function getMinPriorityIndex(LexemeScope $parts) : ?int
	{
		$minPriority = 0;
		$minPriorityIndex = null;
		foreach ($parts->children() as $key => $part)
		{
			//if ($part instanceof LexemeScope) { continue; }
			$value = $part->value();

			foreach (self::PRIORITY as $priority => $operators)
			{
				if (in_array($value, $operators, true) && $priority >= $minPriority)
				{
					if (in_array($value, self::REVERSE_PRIORITY) && $priority === $minPriority)
					{
						continue;
					}
					$minPriority = $priority;
					$minPriorityIndex = $key;
				}
			}
		}

		return $minPriorityIndex;
	}
}