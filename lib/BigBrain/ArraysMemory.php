<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class ArraysMemory
{
	private Stack $stack;
	protected int $offset;
	protected const int CELL_SIZE = 2;
	protected const int MAX_SIZE = 256;

	protected OutputStream $stream;
	protected int $size;

	public function __construct(Stack $stack, OutputStream $stream,  int $offset, int $size)
	{
		$this->stack = $stack;
		$this->stream = $stream;
		$this->offset = $offset;
		$this->size = $size;

		if ($size > 0)
		{
			$this->commentIndexes();
		}
	}

	public function allocate(Type\BaseType $type, Lexeme $name, array $sizes) : MemoryCellArray
	{
		$address = $this->startPosition() + $this->lastIndex() * self::CELL_SIZE; // todo check size
		$startIndex = $this->lastIndex();

		$type = $this->buildType($type, $sizes);
		$cell = new MemoryCellArray($address, $name->value(), $type, $startIndex);

		$this->commentArray($address, $name->value(), $cell->type());

		return $this->stack->push($name, $cell);
	}

	protected function buildType(Type\BaseType $type, array $sizes) : Type\Pointer
	{
		$lastType = $type;
		foreach (array_reverse($sizes) as $size)
		{
			$lastType = new Type\Pointer($lastType, $size);
		}
		return $lastType;
	}

	public function get(Lexeme $name) : MemoryCellArray
	{
		return $this->stack->get($name, MemoryCellArray::class);
	}

	protected function commentIndexes() : void
	{
		$this->stream->memoryComment($this->offset, "adr_s");
		$this->stream->memoryComment($this->offset + 1, "adr_d");
		for ($i = 0; $i < $this->size; $i++)
		{
			$this->stream->memoryComment(
				$this->offset + self::CELL_SIZE + (self::CELL_SIZE * $i),
				"i$i"
			);
		}
	}

	protected function commentArray(int $address, string $varName, Type\Pointer $type) : void
	{
		$sizes = $type->sizes();
		$plainSize = $type->plainSize();
		for ($i = 0; $i < $plainSize; $i++)
		{
			$indexes = Utils\ArraysHelper::complexIndex($i, $sizes);
			$this->stream->memoryComment(
				$address + 1,
				sprintf("%s[%s]", $varName, implode(',', $indexes))
			);
			$address += 2;
		}
	}

	protected function lastIndex() : int
	{
		$result = 0;
		foreach ($this->stack->getAll(MemoryCellArray::class) as $item)
		{
			$result += $item->type()->plainSize();
		}

		return $result;
	}

	protected function startPosition() : int
	{
		return $this->offset + self::CELL_SIZE;
	}
}