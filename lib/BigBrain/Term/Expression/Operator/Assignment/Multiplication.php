<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;

use Gordy\Brainfuck\BigBrain\Environment;
use \Gordy\Brainfuck\BigBrain\Term\Expression;

class Multiplication extends Skeleton
{
	protected function assign(Environment $env) : void
	{
		$this->to->assign($env, $this->value, Expression\Assignable::ASSIGN_MULTIPLY);
	}

	public function __toString() : string
	{
		return sprintf('%s *= %s', $this->to, $this->value);
	}
}