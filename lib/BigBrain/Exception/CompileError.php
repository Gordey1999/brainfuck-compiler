<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Token;

class CompileError extends Exception
{
	public function __construct(string $message, Token $token)
	{
		parent::__construct("COMPILE ERROR:\n$message", $token);
	}
}