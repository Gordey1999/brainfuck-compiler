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

	public function label() : string
	{
		return $this->label;
	}

	public function __toString() : string
	{
		if (preg_match('/R\d+/', $this->label))
		{
			return sprintf('%s', $this->label);
		}
		else
		{
			return sprintf('%s(%s)', $this->label, $this->address);
		}
	}
}