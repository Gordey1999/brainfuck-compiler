<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class Exception extends \Exception
{
	protected array $position;

	public function __construct(string $message, Lexeme $lexeme)
	{
		parent::__construct($message);
		$this->position = $lexeme->position();
		$this->message .= self::getPositionString();
		// todo add line, char to message
		// todo add backtrace(if inside function, will be great!)
		// todo хотя backtrace нужен только для рантайм ошибок, у меня таких не будет
	}

	public function getPositionString() : string
	{
		$line = $this->position[0] + 1;
		$column = $this->position[1] + 1;

		return " at line $line column $column";
	}

	public function getPosition() : array
	{
		return $this->position;
	}
}