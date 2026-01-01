<?php

namespace Gordy\Brainfuck\BigBrain\Precompile;

use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\ArraysMemory as BaseArraysMemory;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\MemoryCellPointer;

class ArraysMemory extends BaseArraysMemory
{
	protected int $memorySize = 0;

	public function allocate(Type\BaseType $type, Lexeme $name, array $sizes) : MemoryCellPointer
	{
		$pointer = new MemoryCellPointer(0, '',  $type, 0, $sizes);
		$this->memorySize += $pointer->plainSize();

		return parent::allocate($type, $name, $sizes);
	}

	public function computedMemorySize() : int
	{
		return $this->memorySize;
	}
}