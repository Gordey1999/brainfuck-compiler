<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class Exception extends \Exception
{
	protected Lexeme $lexeme;

	public function __construct(string $message, Lexeme $lexeme)
	{
		parent::__construct($message);
		$this->lexeme = $lexeme;
		$this->message .= self::getPositionString();
		// todo add backtrace(if inside function, will be great!)
		// todo хотя backtrace нужен только для рантайм ошибок, у меня таких не будет
	}

	public function getPositionString() : string
	{
		if (empty($this->lexeme->value())) { return ''; }

		$position = $this->lexeme->position();

		$line = $position[0] + 1;
		$column = $position[1] + 1;

		return " at line $line column $column";
	}

	public function getLexeme() : Lexeme
	{
		return $this->lexeme;
	}
}