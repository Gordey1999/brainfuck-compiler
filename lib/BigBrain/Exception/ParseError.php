<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class ParseError extends Exception
{
	public function __construct(string $message, Lexeme $lexeme)
	{
		parent::__construct("PARSE ERROR: $message", $lexeme);
	}
}