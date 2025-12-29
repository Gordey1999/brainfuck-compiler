<?php

namespace Gordy\Brainfuck\BigBrain\Type;

class Boolean implements BaseType
{
	public function size() : int
	{
		return 1;
	}
}