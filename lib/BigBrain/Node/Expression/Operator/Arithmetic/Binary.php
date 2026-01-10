<?php

namespace Gordy\Brainfuck\BigBrain\Node\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Node\Expression;

abstract class Binary extends Expression\Operator\Binary
{
	protected abstract function computeValue(int $left, int $right) : int;

	protected function computeResultType() : Type\BaseType
	{
		return new Type\Byte();
	}
}