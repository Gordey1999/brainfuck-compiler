<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

use Gordy\Brainfuck\BigBrain\Exception\ParseError;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Parser\LexemeScope;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\Expression\Operator;

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
		[ '=', '+=', '-=', '*=', '/=', '%=' ],
		[ ',' ],
	];

	public const array SINGLE_OPERATORS = [
		'++', '--', '!', '[', '(', '.'
	];

	public const array REVERSE_PRIORITY = [
		'=', '+=', '-=', '*=', '/=', '%=',
	];

	protected Names $names;

	public function __construct(Names $names)
	{
		$this->names = $names;
	}

	public function build(LexemeScope $scope) : Expression
	{
		return $this->parseExpression($scope);
	}

	protected function parseExpression(LexemeScope $scope) : Expression
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

	protected function parseSingeExpression(Lexeme $lexeme) : Expression
	{
		if ($lexeme->isLiteral()) // 5
		{
			return new Expression\Literal($lexeme);
		}
		else if ($lexeme->isName()) // a
		{
			if ($this->names->isVariable($lexeme->value()))
			{
				return new Expression\ScalarVariable($lexeme);
			}
			else if ($this->names->isArray($lexeme->value()))
			{
				return new Expression\ArrayVariable($lexeme);
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
			return new Expression\ArrayScope(
				$this->parseExpression($lexeme),
				$lexeme,
			);
		}
		else
		{
			throw new ParseError("can't parse expression", $lexeme);
		}
	}

	protected function parseAccess(LexemeScope $scope) : Expression
	{
		$last = $scope->last();
		if ($last instanceof LexemeScope)
		{
			if ($last->value() === '[')
			{
				return new Operator\ArrayAccess(
					$this->parseAccess($scope->slice(0, -1)),
					$last->empty() ? new Expression\None() : $this->parseExpression($last),
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
			return $this->parseExpression($scope); // todo int a = 5 - everlasting cycle
		}
	}

	protected function parseSingleOperatorBefore(Expression $operand, Lexeme $operator) : Expression
	{
		return match ($operator->value()) {
			'++' => new Operator\Arithmetic\Increment($operand, $operator),
			'--' => new Operator\Arithmetic\Decrement($operand, $operator),
			default => throw new ParseError("can't parse expression", $operator),
		};
	}

	protected function parseSingleOperatorAfter(Expression $operand, Lexeme $operator) : Expression
	{
		return match ($operator->value()) {
			'++' => new Operator\Arithmetic\Increment($operand, $operator, true),
			'--' => new Operator\Arithmetic\Decrement($operand, $operator, true),
			default => throw new ParseError("can't parse expression", $operator),
		};
	}

	protected function parseBinaryOperator(Expression $left, Expression $right, Lexeme $operator) : Expression
	{
		return match($operator->value()) {
			'+' =>               new Operator\Arithmetic\Addition($left, $right, $operator),
			'-' =>               new Operator\Arithmetic\Subtraction($left, $right, $operator),
			'*' =>               new Operator\Arithmetic\Multiplication($left, $right, $operator),
			'/' =>               new Operator\Arithmetic\Division($left, $right, $operator),
			'%' =>               new Operator\Arithmetic\Modulo($left, $right, $operator),

			'==', '===' =>       new Operator\Logical\Equals($left, $right, $operator),
			'!=', '!==', '<>' => new Operator\Logical\NotEquals($left, $right, $operator),
			'>' =>               new Operator\Logical\More($left, $right, $operator),
			'>=' =>              new Operator\Logical\MoreOrEquals($left, $right, $operator),
			'<' =>               new Operator\Logical\Less($left, $right, $operator),
			'<=' =>              new Operator\Logical\LessOrEquals($left, $right, $operator),

			'=' =>               new Operator\Assignment\Base($left, $right, $operator),
			'+=' =>              new Operator\Assignment\Addition($left, $right, $operator),
			'-=' =>              new Operator\Assignment\Substraction($left, $right, $operator),
			'*=' =>              new Operator\Assignment\Multiplication($left, $right, $operator),
			'/=' =>              new Operator\Assignment\Division($left, $right, $operator),
			'%=' =>              new Operator\Assignment\Modulo($left, $right, $operator),

			',' =>               new Operator\Comma($left, $right, $operator),

			default =>           throw new SyntaxError('unknown operator', $operator),
		};
	}

	protected function getMinPriorityIndex(LexemeScope $parts) : ?int
	{
		$minPriority = 0;
		$minPriorityIndex = null;
		foreach ($parts->children() as $key => $part)
		{
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