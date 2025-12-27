<?php

namespace Gordy\Brainfuck\BigBrain\Term;

use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

trait HasLexeme
{
	protected Lexeme $lexeme;

	public function lexeme() : Lexeme
	{
		return $this->lexeme;
	}
}