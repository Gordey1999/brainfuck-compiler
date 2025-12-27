<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression\Operator\Arithmetic;

class Subtraction extends Skeleton
{
	protected function computeValue(int $left, int $right) : int
	{
		return $left - $right;
	}
}