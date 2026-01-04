<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class CompileError extends Exception
{
	public function __construct(string $message, Lexeme $lexeme)
	{
		parent::__construct("COMPILE ERROR:\n$message", $lexeme);
	}
}