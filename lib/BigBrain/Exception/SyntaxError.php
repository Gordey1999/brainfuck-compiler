<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Token;

class SyntaxError extends Exception
{
	public function __construct(string $message, Token $token)
	{
		parent::__construct("SYNTAX ERROR:\n$message", $token);
	}
}