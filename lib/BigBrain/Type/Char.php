<?php

namespace Gordy\Brainfuck\BigBrain\Type;

class Char implements Scalar
{
	public function __toString() : string
	{
		return 'char';
	}
}