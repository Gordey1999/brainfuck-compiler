<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class FakeProcessor extends Processor
{
	protected int $maxRegistrySize = 0;
	protected int $registrySize = 0;

	public function __construct(OutputStream $stream, int $registrySize)
	{
		parent::__construct($stream, $registrySize);
		$this->stream = $stream;
	}

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