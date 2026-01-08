<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Logical;

use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Term\Expression;

abstract class Binary extends Expression\Operator\Binary
{
	protected abstract function computeValue(int $left, int $right) : bool;

	protected function computeResultType() : Type\BaseType
	{
		return new Type\Boolean();
	}
}