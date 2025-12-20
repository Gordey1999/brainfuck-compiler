<?php

namespace Gordy\Brainfuck\Compiler\Exception;

class ParseError extends Exception
{
	public function __construct(string $message, int $index)
	{
		parent::__construct("PARSE ERROR: $message", $index);
	}
}