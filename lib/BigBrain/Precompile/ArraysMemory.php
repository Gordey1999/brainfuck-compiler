<?php

namespace Gordy\Brainfuck\BigBrain\Precompile;

use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\ArraysMemory as BaseArraysMemory;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\MemoryCellArray;

class ArraysMemory extends BaseArraysMemory
{
	protected int $memorySize = 0;

	public function allocate(Type\BaseType $type, Token $name, array $sizes) : MemoryCellArray
	{
		$cell = parent::allocate($type, $name, $sizes);
		$this->memorySize += $cell->type()->plainSize();
		return $cell;
	}

	public function computedMemorySize() : int
	{
		return $this->memorySize;
	}
}