<?php

namespace Gordy\Brainfuck\BigBrain\Exception;

use Gordy\Brainfuck\BigBrain\Parser\Token;

class Exception extends \Exception
{
	protected Token $token;

	public function __construct(string $message, Token $token)
	{
		parent::__construct($message);
		$this->token = $token;
		$this->message .= self::getPositionString();
		// todo add backtrace(if inside function, will be great!)
		// todo хотя backtrace нужен только для рантайм ошибок, у меня таких не будет
	}

	public function getPositionString() : string
	{
		if (empty($this->token->value())) { return ''; }

		$position = $this->token->position();

		$line = $position[0] + 1;
		$column = $position[1] + 1;

		return " at line $line column $column";
	}

	public function getToken() : Token
	{
		return $this->token;
	}
}