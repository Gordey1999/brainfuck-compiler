<?php

namespace Gordy\Brainfuck\BigBrain\Node;

use Gordy\Brainfuck\BigBrain\Parser\Token;

trait HasToken
{
	protected Token $token;

	public function token() : Token
	{
		return $this->token;
	}
}