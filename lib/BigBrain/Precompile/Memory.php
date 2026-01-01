<?php

namespace Gordy\Brainfuck\BigBrain\Precompile;

use Gordy\Brainfuck\BigBrain\MemoryCellTyped;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Memory as BaseMemory;

class Memory extends BaseMemory
{
	protected int $memorySize = 0;

	public function allocate(Type\BaseType $type, Lexeme $name) : MemoryCellTyped
	{
		$this->memorySize++;

		return parent::allocate($type, $name);
	}

	public function computedMemorySize() : int
	{
		return $this->memorySize;
	}
}