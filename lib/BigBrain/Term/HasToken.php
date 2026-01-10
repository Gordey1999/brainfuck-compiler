<?php

namespace Gordy\Brainfuck\BigBrain\Term;

use Gordy\Brainfuck\BigBrain\Parser\Token;

trait HasToken
{
	protected Token $token;

	public function token() : Token
	{
		return $this->token;
	}
}