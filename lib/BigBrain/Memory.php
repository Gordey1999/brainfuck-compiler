<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Parser\Token;

class Memory
{
	protected Stack $stack;
	protected int $offset;

	protected OutputStream $stream;

	public function __construct(Stack $stack, OutputStream $stream, int $offset)
	{
		$this->stack = $stack;
		$this->offset = $offset;
		$this->stream = $stream;
	}

	public function allocate(Type\BaseType $type, Token $name) : MemoryCellTyped
	{
		$address = $this->offset + $this->count();

		$this->stream->memoryComment($address, $name->value());
		$cell = new MemoryCellTyped($address, $name->value(), $type);

		return $this->stack->push($name, $cell);
	}

	protected function count() : int
	{
		$variables = $this->stack->getAll(MemoryCellTyped::class);
		return count($variables);
	}

	public function get(Token $name) : MemoryCellTyped
	{
		return $this->stack->get($name, MemoryCellTyped::class);
	}
}