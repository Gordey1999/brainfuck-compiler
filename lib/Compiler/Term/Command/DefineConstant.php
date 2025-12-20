<?php

namespace Gordy\Brainfuck\Compiler\Term\Command;

use Gordy\Brainfuck\Compiler\Term;

class DefineConstant implements Term\Command
{
	public function __construct(string $name, Term\Expression $value)
	{

	}
}