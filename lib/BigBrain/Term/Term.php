<?php

namespace Gordy\Brainfuck\BigBrain\Term;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

interface Term
{
	public function compile(Environment $env) : void;

	public function lexeme() : Lexeme;

	public function __toString() : string;
}