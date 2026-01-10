<?php

namespace Gordy\Brainfuck\BigBrain\Term;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Parser\Token;

interface Term
{
	public function compile(Environment $env) : void;

	public function token() : Token;

	public function __toString() : string;
}