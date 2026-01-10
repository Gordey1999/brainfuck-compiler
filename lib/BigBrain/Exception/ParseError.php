<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Token;

class ParseError extends Exception
{
	public function __construct(string $message, Token $token)
	{
		parent::__construct("PARSE ERROR:\n$message", $token);
	}
}