<?php

namespace Gordy\Brainfuck\Compiler\Exception;

class SyntaxError extends Exception
{
	public function __construct(string $message, int $index)
	{
		parent::__construct("SYNTAX ERROR: $message", $index);
	}
}