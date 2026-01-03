<?php

namespace Gordy\Brainfuck\BigBrain\Type;

class Byte implements Scalar
{
	public function __toString() : string
	{
		return 'byte';
	}
}