<?php

namespace Gordy\Brainfuck\BigBrain;

class MemoryCellArray extends MemoryCell
{
	protected int $startIndex;
	protected Type\Type $type;

	public function __construct(int $address, string $label, Type\Pointer $type, int $startIndex)
	{
		parent::__construct($address, $label);
		$this->startIndex = $startIndex;
		$this->type = $type;
	}

	public function address() : int
	{
		throw new \Exception('Not supported. Use startIndex instead.');
	}

	public function startIndex() : int
	{
		return $this->startIndex;
	}

	public function type() : Type\Pointer
	{
		return $this->type;
	}
}