<?php

namespace Gordy\Brainfuck\BigBrain;

class MemoryCellPointer extends MemoryCellTyped
{
	protected array $sizes;
	protected int $relativeAddress;

	public function __construct(int $address, string $label, Type\BaseType $type, int $relativeAddress, array $sizes)
	{
		parent::__construct($address, $label, $type);
		$this->sizes = $sizes;
		$this->relativeAddress = $relativeAddress;
	}

	public function address() : int
	{
		throw new \Exception('Not supported. Use relativeAddress instead.');
	}

	public function relativeAddress() : int
	{
		return $this->relativeAddress;
	}

	public function sizes() : array
	{
		return $this->sizes;
	}

	public function plainSize() : int
	{
		return array_product($this->sizes);
	}
}