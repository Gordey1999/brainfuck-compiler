<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

use Gordy\Brainfuck\BigBrain\Exception\ParseError;

class TokenStream
{
	/** @var Token[] */
	protected array $tokens;
	protected $pos = 0;

	public function __construct(array $tokens)
	{
		$this->tokens = $tokens;
	}

	public function peek() : ?string
	{
		return $this->tokens[$this->pos]?->value() ?? null;
	}

	public function peekObj() : ?Token
	{
		return $this->tokens[$this->pos] ?? null;
	}

	public function last() : ?string
	{
		return $this->tokens[$this->pos - 1]?->value() ?? null;
	}

	public function lastObj() : ?Token
	{
		return $this->tokens[$this->pos - 1] ?? null;
	}

	public function next() : ?string
	{
		return $this->tokens[$this->pos++]?->value() ?? null;
	}

	public function nextObj() : ?Token
	{
		return $this->tokens[$this->pos++] ?? null;
	}

	public function eat(...$expected) : bool
	{
		if (in_array($this->peek(), $expected, true))
		{
			$this->pos++;
			return true;
		}
		return false;
	}

	public function eatName() : bool
	{
		if ($this->peekObj()->isName())
		{
			$this->pos++;
			return true;
		}
		return false;
	}

	public function eatLiteral() : bool
	{
		if ($this->peekObj()->isLiteral())
		{
			$this->pos++;
			return true;
		}
		return false;
	}

	public function has() : bool
	{
		return $this->peekObj() !== null;
	}
}