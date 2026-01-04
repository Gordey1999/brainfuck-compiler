<?php

namespace Gordy\Brainfuck\BigBrain\Type;

class Pointer implements BaseType
{
	protected int $size;
	protected BaseType $valueType;

	public function __construct(BaseType $valueType, int $size)
	{
		$this->valueType = $valueType;
		$this->size = $size;
	}

	public function size() : int
	{
		return $this->size;
	}

	public function sizes() : array
	{
		if ($this->valueType instanceof self)
		{
			return [$this->size(), ...$this->valueType->sizes()];
		}
		return [$this->size()];
	}

	public function valueType() : BaseType
	{
		return $this->valueType;
	}

	public function plainSize() : int
	{
		return array_product($this->sizes());
	}

	public function __toString() : string
	{
		$subType = $this->valueType();
		return "array<$subType>";
	}
}