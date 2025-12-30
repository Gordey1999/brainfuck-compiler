<?php

namespace Gordy\Brainfuck\BigBrain;

class MemoryCellTyped extends MemoryCell
{
	protected int $address;
	protected string $label;
	protected Type\BaseType $type;

	public function __construct(int $address, string $label, Type\BaseType $type)
	{
		parent::__construct($address, $label);
		$this->type = $type;
	}

	public function type() : Type\BaseType
	{
		return $this->type;
	}
}