<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Assignment;

use Gordy\Brainfuck\BigBrain\Environment;
use \Gordy\Brainfuck\BigBrain\Node\Expression;

class Base extends Skeleton
{
	/** @return Expression\ScalarVariable[] */
	public function variables() : array
	{
		$result = [ $this->to ];
		if ($this->value instanceof self) // a = (b = (c = 0));
		{
			array_push($result, ...$this->value->variables());
		}

		return $result;
	}

	public function left() : Expression
	{
		return $this->to;
	}

	public function right() : Expression
	{
		return $this->value;
	}

	protected function assign(Environment $env) : void
	{
		$this->to->assign($env, $this->value, Expression\Assignable::ASSIGN_SET);
	}

	public function __toString() : string
	{
		return sprintf('%s = %s', $this->to, $this->value);
	}
}