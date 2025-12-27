<?php

namespace Gordy\Brainfuck\BigBrain\Term;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

interface Term
{
	public function compile(BigBrain\Environment $env) : void;

	public function lexeme() : Lexeme;

	public function __toString() : string;
}