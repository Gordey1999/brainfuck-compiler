<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Node\Expression;
use Gordy\Brainfuck\BigBrain\Environment;

class Decrement extends Increment
{
	public function calculate(Environment $env) : void
	{
		$this->to->assign($env, $this->value, Expression\Assignable::ASSIGN_SUB);
	}

	public function __toString() : string
	{
		if ($this->isPost)
		{
			return sprintf('%s--', $this->to);
		}
		else
		{
			return sprintf('--%s', $this->to);
		}
	}
}