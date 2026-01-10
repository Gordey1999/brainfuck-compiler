<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

use Gordy\Brainfuck\BigBrain\Exception\ParseError;
use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Parser\TokenScope;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\Expression\Operator;

class ExpressionBuilder
{
	public const array PRIORITY = [
		[ '[', '(', '.' ],
		[ '++', '--' ],
		[ 'sizeof' ],
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
		'++', '--', '!', '[', '(', 'sizeof',
	];

	public const array REVERSE_PRIORITY = [
		'=', '+=', '-=', '*=', '/=', '%=',
	];

	protected Names $names;

	public function __construct(Names $names)
	{
		$this->names = $names;
	}

	public function build(TokenScope $scope) : Expression
	{
		return $this->parseExpression($scope);
	}

	protected function parseExpression(TokenScope $scope) : Expression
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

	protected function parseSingeExpression(Token $token) : Expression
	{
		if ($token->isLiteral()) // 5
		{
			return new Expression\Literal($token);
		}
		else if ($token->isName()) // a
		{
			if ($this->names->isVariable($token->value()))
			{
				return new Expression\ScalarVariable($token);
			}
			else if ($this->names->isArray($token->value()))
			{
				return new Expression\ArrayVariable($token);
			}
			else
			{
				throw new ParseError("can't parse expression", $token);
			}
		}
		else if ($token instanceof TokenScope && $token->value() === '(') // (a + b)
		{
			return $this->parseExpression($token);
		}
		else if ($token instanceof TokenScope && $token->value() === '[') // [1, 2, 3]
		{
			return new Expression\ArrayScope(
				$this->parseExpression($token),
				$token,
			);
		}
		else
		{
			throw new ParseError("can't parse expression", $token);
		}
	}

	protected function parseAccess(TokenScope $scope) : Expression
	{
		$last = $scope->last();
		if ($last instanceof TokenScope)
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

	protected function parseSingleOperatorBefore(Expression $operand, Token $operator) : Expression
	{
		return match ($operator->value()) {
			'++'     => new Operator\Arithmetic\Increment($operand, $operator),
			'--'     => new Operator\Arithmetic\Decrement($operand, $operator),
			'!'      => new Operator\Logical\Not($operand, $operator),
			'sizeof' => new Operator\Sizeof($operand, $operator),
			default  => throw new ParseError("can't parse expression", $operator),
		};
	}

	protected function parseSingleOperatorAfter(Expression $operand, Token $operator) : Expression
	{
		return match ($operator->value()) {
			'++'    => new Operator\Arithmetic\Increment($operand, $operator, true),
			'--'    => new Operator\Arithmetic\Decrement($operand, $operator, true),
			default => throw new ParseError("can't parse expression", $operator),
		};
	}

	protected function parseBinaryOperator(Expression $left, Expression $right, Token $operator) : Expression
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
			'||' =>              new Operator\Logical\One($left, $right, $operator),
			'&&' =>              new Operator\Logical\Both($left, $right, $operator),

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

	protected function getMinPriorityIndex(TokenScope $parts) : ?int
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