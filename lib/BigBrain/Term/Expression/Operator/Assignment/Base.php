<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Assignment;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use \Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Term\HasLexeme;
use Gordy\Brainfuck\BigBrain\Type;

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