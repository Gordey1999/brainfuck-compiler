<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Term\Expression;

interface Assignable
{
	public function assign(Environment $env, Expression $value) : void;
}