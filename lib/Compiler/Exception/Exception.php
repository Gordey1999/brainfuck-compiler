<?php

namespace Gordy\Brainfuck\Compiler\Exception;

class Exception extends \Exception
{
	protected int $index = 0;

	function __construct(string $message, int $index)
	{
		parent::__construct($message, $index);
		$this->index = $index;
		// todo add line, char to message
		// todo add backtrace(if inside function, will be great!)
		// todo хотя backtrace нужен только для рантайм ошибок, у меня таких не будет
	}
}