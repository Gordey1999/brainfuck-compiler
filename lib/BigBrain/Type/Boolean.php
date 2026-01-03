<?php

namespace Gordy\Brainfuck\BigBrain\Type;

class Boolean implements Scalar
{
	public function __toString() : string
	{
		return 'bool';
	}
}