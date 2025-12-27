<?php

namespace Gordy\Brainfuck\BigBrain\Term;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Type;

interface Expression extends Term
{
	public function calculate(Environment $env, int $resultAddress) : void;

	public function isComputable(Environment $env) : bool;

	public function compute(Environment $env) : Type\Computable;
}