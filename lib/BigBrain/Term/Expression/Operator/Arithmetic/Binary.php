<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Term\Expression;

abstract class Binary extends Expression\Operator\Binary
{
	protected function computeResultType() : Type\BaseType
	{
		return new Type\Byte();
	}
}