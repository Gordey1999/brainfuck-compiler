<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class SyntaxError extends Exception
{
	public function __construct(string $message, Lexeme $lexeme)
	{
		parent::__construct("SYNTAX ERROR:\n$message", $lexeme);
	}
}