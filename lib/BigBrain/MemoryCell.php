<?php

namespace Gordy\Brainfuck\BigBrain;

class MemoryCell
{
	protected int $address;
	protected string $label;

	public function __construct(int $address, string $label)
	{
		$this->address = $address;
		$this->label = $label;
	}

	public function address() : int
	{
		return $this->address;
	}

	public function __toString() : string
	{
		return sprintf('%s(%s)', $this->label, $this->address);
	}
}