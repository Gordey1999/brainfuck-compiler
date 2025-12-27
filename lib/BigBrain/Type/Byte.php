<?php

namespace Gordy\Brainfuck\BigBrain\Type;

class Byte implements Type
{
	public function size() : int
	{
		return 1;
	}
}