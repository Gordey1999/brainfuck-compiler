<?php

namespace Gordy\Brainfuck\BigBrain\Precompile;

use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Processor as BaseProcessor;

class Processor extends BaseProcessor
{
	protected int $maxRegistrySize = 0;
	protected int $registrySize = 0;

	public function reserve(MemoryCell ...$near) : MemoryCell
	{
		$this->registrySize++;
		if ($this->registrySize > $this->maxRegistrySize)
		{
			$this->maxRegistrySize = $this->registrySize;
		}

		return parent::reserve(...$near);
	}

	public function release(MemoryCell ...$addresses) : void
	{
		$this->registrySize -= count($addresses);

		parent::release(...$addresses);
	}

	public function computedRegistrySize() : int
	{
		if ($this->registrySize !== 0)
		{
			throw new CompileError(
				sprintf("memory leak detected: %s byte(s)", $this->registrySize),
				new Lexeme('')
			);
		}

		return $this->maxRegistrySize;
	}
}