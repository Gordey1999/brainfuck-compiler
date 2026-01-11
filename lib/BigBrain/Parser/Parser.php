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
		$statements = [];

		while ($this->stream->has())
		{
			$statements[] = $this->parseStatement();
		}

		return new Node\Scope($statements);
	}

	protected function parseStatement() : Node\Node
	{
		if ($this->stream->eat('{'))
		{
			return $this->parseBlock();
		}
		if ($this->stream->eat('if'))
		{
			return $this->parseIf();
		}
		if ($this->stream->eat('while'))
		{
			return $this->parseWhile();
		}
		if ($this->stream->eat('do'))
		{
			return $this->parseDoWhile();
		}
		else
		{
			$result = $this->parseCommand();
			$this->parseSplitter();
			return $result;
		}
	}

	private function parseBlock() : Node\Scope
	{
		$statements = [];

		while ($this->stream->peek() !== '}' && $this->stream->peek() !== null)
		{
			$statements[] = $this->parseStatement();
		}

		if (!$this->stream->eat('}'))
		{
			throw new ParseError("'}' expected", $this->stream->lastObj());
		}

		return new Node\Scope($statements);
	}

	private function parseSplitter() : void
	{
		if (!$this->stream->eat(';'))
		{
			throw new ParseError("';' expected", $this->stream->lastObj());
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
		$token = $this->stream->lastObj();
		$condition = $this->parseCondition();

		$thenBranch = $this->parseStatement();

		if ($this->stream->eat('else'))
		{
			$elseBranch = $this->parseStatement();
		}
		else
		{
			$elseBranch = new Node\Scope([]);
		}

		if (!$thenBranch instanceof Node\Scope)
		{
			$thenBranch = new Node\Scope([$thenBranch]);
		}
		if (!$elseBranch instanceof Node\Scope)
		{
			$elseBranch = new Node\Scope([$elseBranch]);
		}

		return new Node\Structure\IfCondition($condition, $thenBranch, $elseBranch, $token);
	}

	protected function parseWhile() : Node\Structure\WhileLoop
	{
		$token = $this->stream->lastObj();
		$condition = $this->parseCondition();

		$body = $this->parseStatement();

		if (!$body instanceof Node\Scope)
		{
			$body = new Node\Scope([$body]);
		}

		return new Node\Structure\WhileLoop($condition, $body, $token);
	}

	protected function parseDoWhile() : Node\Structure\DoWhileLoop
	{
		$token = $this->stream->lastObj();

		$body = $this->parseStatement();

		if (!$this->stream->eat('while'))
		{
			throw new ParseError("'while' expected", $this->stream->lastObj());
		}
		$condition = $this->parseCondition();
		$this->parseSplitter();

		if (!$body instanceof Node\Scope)
		{
			$body = new Node\Scope([$body]);
		}

		return new Node\Structure\DoWhileLoop($condition, $body, $token);
	}

	protected function parseCondition() : Node\Expression
	{
		if (!$this->stream->eat('('))
		{
			throw new ParseError("'(' expected", $this->stream->nextObj());
		}

		$result =  $this->parseExpression();

		if (!$this->stream->eat(')'))
		{
			throw new ParseError("')' expected", $this->stream->nextObj());
		}

		return $result;
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