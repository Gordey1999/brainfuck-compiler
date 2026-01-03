<?php

namespace Gordy\Brainfuck\BigBrain\Type;

interface BaseType extends Type
{
	public function __toString(): string;
}