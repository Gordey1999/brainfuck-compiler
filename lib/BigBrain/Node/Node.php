<?php

namespace Gordy\Brainfuck\BigBrain\Node;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Parser\Token;

interface Node
{
	public function compile(Environment $env) : void;

	public function token() : Token;

	public function __toString() : string;
}