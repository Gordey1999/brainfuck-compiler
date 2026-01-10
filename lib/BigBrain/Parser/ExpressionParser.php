<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

use Gordy\Brainfuck\BigBrain\Exception\ParseError;
use Gordy\Brainfuck\BigBrain\Node\Expression;
use Gordy\Brainfuck\BigBrain\Node\Expression\Operator;

class ExpressionParser
{
	protected TokenStream $stream;
	protected Names $names;
	protected bool $isInit = false;

	public function __construct(TokenStream $stream, Names $names)
	{
		$this->stream = $stream;
		$this->names = $names;
	}

	public function initMode(bool $value) : void
	{
		$this->isInit = $value;
	}

	public function parse() : Expression
	{
		$left = $this->parseAssignment();

		// a, b, c - левая ассоциативность
		while ($this->stream->eat(','))
		{
			$operator = $this->stream->lastObj();
			$right = $this->parseAssignment();

			$left = new Operator\Comma($left, $right, $operator);
		}
		return $left;
	}

	protected function parseAssignment() : Expression
	{
		$left = $this->parseLogic();

		// a = b = с - правая ассоциативность
		if ($this->stream->eat('=', '+=', '-=', '*=', '/=', '%='))
		{
			$operator = $this->stream->lastObj();
			$right = $this->parseAssignment();

			$left = match($operator->value()) {
				'=' =>  new Operator\Assignment\Base($left, $right, $operator),
				'+=' => new Operator\Assignment\Addition($left, $right, $operator),
				'-=' => new Operator\Assignment\Substraction($left, $right, $operator),
				'*=' => new Operator\Assignment\Multiplication($left, $right, $operator),
				'/=' => new Operator\Assignment\Division($left, $right, $operator),
				'%=' => new Operator\Assignment\Modulo($left, $right, $operator),
			};
		}
		return $left;
	}

	public function parseLogic() : Expression
	{
		$left = $this->parseComparison();

		while ($this->stream->eat('&&', '||'))
		{
			$operator = $this->stream->lastObj();
			$right = $this->parseComparison();

			$left = match($operator->value()) {
				'||' => new Operator\Logical\One($left, $right, $operator),
				'&&' => new Operator\Logical\Both($left, $right, $operator),
			};
		}
		return $left;
	}

	public function parseComparison() : Expression
	{
		$left = $this->parseMoreLess();

		while ($this->stream->eat('==', '!=', '===', '!==', '<>'))
		{
			$operator = $this->stream->lastObj();
			$right = $this->parseMoreLess();

			$left = match($operator->value()) {
				'==', '==='       => new Operator\Logical\Equals($left, $right, $operator),
				'!=', '!==', '<>' => new Operator\Logical\NotEquals($left, $right, $operator),
			};
		}
		return $left;
	}

	public function parseMoreLess() : Expression
	{
		$left = $this->parseAddition();

		while ($this->stream->eat('<', '<=', '>', '>='))
		{
			$operator = $this->stream->lastObj();
			$right = $this->parseAddition();

			$left = match($operator->value()) {
				'>'  => new Operator\Logical\More($left, $right, $operator),
				'>=' => new Operator\Logical\MoreOrEquals($left, $right, $operator),
				'<'  => new Operator\Logical\Less($left, $right, $operator),
				'<=' => new Operator\Logical\LessOrEquals($left, $right, $operator),
			};
		}
		return $left;
	}

	protected function parseAddition() : Expression
	{
		$left = $this->parseMultiplication();

		while ($this->stream->eat('+', '-'))
		{
			$operator = $this->stream->lastObj();
			$right = $this->parseMultiplication();

			$left = match($operator->value()) {
				'+' => new Operator\Arithmetic\Addition($left, $right, $operator),
				'-' => new Operator\Arithmetic\Subtraction($left, $right, $operator),
			};
		}
		return $left;
	}

	protected function parseMultiplication() : Expression
	{
		$left = $this->parseUnary();

		while ($this->stream->eat('*', '/', '%'))
		{
			$operator = $this->stream->lastObj();
			$right = $this->parseUnary();

			$left = match($operator->value()) {
				'*' => new Operator\Arithmetic\Multiplication($left, $right, $operator),
				'/' => new Operator\Arithmetic\Division($left, $right, $operator),
				'%' => new Operator\Arithmetic\Modulo($left, $right, $operator),
			};
		}
		return $left;
	}

	protected function parseUnary() : Expression
	{
		if ($this->stream->eat('++', '--', '!', 'sizeof'))
		{
			$operator = $this->stream->lastObj();
			$operand = $this->parseUnary();

			return match ($operator->value()) {
				'++'     => new Operator\Arithmetic\Increment($operand, $operator),
				'--'     => new Operator\Arithmetic\Decrement($operand, $operator),
				'!'      => new Operator\Logical\Not($operand, $operator),
				'sizeof' => new Operator\Sizeof($operand, $operator),
			};
		}
		return $this->parsePostfix();
	}

	protected function parsePostfix() : Expression
	{
		$node = $this->parsePrimary();

		while ($this->stream->eat('[', '++', '--'))
		{
			$operator = $this->stream->lastObj();

			if ($operator->value() === '[')
			{
				if ($node instanceof Expression\ScalarVariable)
				{
					$this->isInit && $this->names->remember($node->name()->value(), Names::ARRAY);
					$node = new Expression\ArrayVariable($node->name());
				}

				if ($this->stream->peek() === ']')
				{
					$index = new Expression\None();
				}
				else
				{
					$index = $this->parse();
				}

				if (!$this->stream->eat(']'))
				{
					throw new ParseError("']' expected", $this->stream->lastObj());
				}

				$node = new Operator\ArrayAccess($node, $index, $operator);

			}
			else
			{
				$node = match ($operator->value()) {
					'++' => new Operator\Arithmetic\Increment($node, $operator, true),
					'--' => new Operator\Arithmetic\Decrement($node, $operator, true),
				};
			}
		}
		return $node;
	}

	protected function parsePrimary(bool $isArray = false) : Expression
	{
		if ($this->stream->eat('['))
		{
			$start = $this->stream->lastObj();
			$inner = $this->parse();

			if (!$this->stream->eat(']'))
			{
				throw new ParseError("']' expected", $this->stream->lastObj());
			}

			return new Expression\ArrayScope($inner, $start);
		}

		if ($this->stream->eat('('))
		{
			$node = $this->parse();

			if (!$this->stream->eat(')'))
			{
				throw new ParseError("')' expected", $this->stream->lastObj());
			}

			return $node;
		}

		if ($this->stream->eatName())
		{
			$name = $this->stream->lastObj();
			if ($this->names->isArray($name->value()))
			{
				return new Expression\ArrayVariable($this->stream->lastObj());
			}
			else
			{
				return new Expression\ScalarVariable($this->stream->lastObj());
			}
		}
		if ($this->stream->eatLiteral())
		{
			return new Expression\Literal($this->stream->lastObj());
		}

		throw new ParseError("expression expected", $this->stream->lastObj());
	}
}