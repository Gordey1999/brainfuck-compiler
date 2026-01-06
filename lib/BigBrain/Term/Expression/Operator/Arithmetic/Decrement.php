<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Environment;

class Decrement extends Increment
{
	public function compile(Environment $env) : void
	{
		$env->stream()->blockComment($this);
		$this->to->assign($env, $this->value, Expression\Assignable::ASSIGN_SUB);
	}
}