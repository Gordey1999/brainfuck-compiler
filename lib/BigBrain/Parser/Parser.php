<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

use Gordy\Brainfuck\BigBrain\Exception\ParseError;
use Gordy\Brainfuck\BigBrain\Node;
use Gordy\Brainfuck\BigBrain\Type;

class Parser
{

	protected TokenStream $stream;
	protected ExpressionParser $expression;
	protected Names $names;

	public function __construct(TokenStream $stream)
	{
		$this->stream = $stream;
		$this->names = new Names();
		$this->expression = new ExpressionParser($this->stream, $this->names);
	}

	public function parse() : Node\Scope
	{
		$list = [];

		while ($this->stream->has())
		{
			$list[] = $this->parseStatement();
		}

		return new Node\Scope($list);
	}

	protected function parseStatement() : Node\Node
	{
		if ($this->stream->eat('if'))
		{
			return $this->parseIf();
		}
		else
		{
			$result = $this->parseCommand();

			if (!$this->stream->eat(';'))
			{
				throw new ParseError("';' expected", $this->stream->lastObj());
			}
			return $result;
		}
	}

	protected function parseCommand() : Node\Node
	{
		if ($this->stream->eat('in'))
		{
			$token = $this->stream->lastObj();
			return new Node\Command\Input($this->parseExpression(), $token);
		}
		else if ($this->stream->eat('out'))
		{
			$token = $this->stream->lastObj();
			return new Node\Command\Output($this->parseExpression(), $token);
		}
		else if ($this->stream->eat('char', 'byte', 'bool'))
		{
			$token = $this->stream->lastObj();

			return new Node\Command\DefineVariable(
				match ($this->stream->last()) {
					'char' => new Type\Char,
					'byte'  => new Type\Byte,
					'bool' => new Type\Boolean,
				},
				$this->parseExpressionInit(),
				$token
			);
		}
		else
		{
			return $this->parseExpression();
		}
	}

	protected function parseIf() : Node\Structure\IfCondition
	{
		$condition = $this->parseCondition();

		$thenBranch = $this->parseStatement();
		$elseBranch = null;

		if ($this->match('else'))
		{
			$elseBranch = $this->parseStatement();
		}

		return new Node\Structure\IfCondition($condition, $thenBranch, $elseBranch, $this->stream->lastObj());
	}

	protected function parseCondition() : Node\Expression
	{
		if (!$this->stream->eat('('))
		{
			throw new ParseError("'(' expected", $this->stream->peekObj());
		}
		$this->parseExpression($this->stream->readUntil(')'));

		if (!$this->stream->eat(')'))
		{
			throw new ParseError("')' expected", $this->stream->peekObj());
		}
	}

	protected function parseExpression() : Node\Expression
	{
		return $this->expression->parse();
	}

	protected function parseExpressionInit() : Node\Expression
	{
		$this->expression->initMode(true);
		$result = $this->expression->parse();
		$this->expression->initMode(false);
		return $result;
	}
}