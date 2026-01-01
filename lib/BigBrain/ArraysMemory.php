<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class ArraysMemory
{
	/** @var array<string, MemoryCellPointer> */
	private array $stack = [];
	protected int $offset;
	protected const int CELL_SIZE = 2;
	protected const int MAX_SIZE = 256;

	protected OutputStream $stream;
	protected Processor $processor;
	protected int $size;

	public function __construct(OutputStream $stream, Processor $processor,  int $offset, int $size)
	{
		$this->stream = $stream;
		$this->processor = $processor;
		$this->offset = $offset;
		$this->size = $size;

		if ($size > 0)
		{
			$this->commentIndexes();
		}
	}

	public function allocate(Type\BaseType $type, Lexeme $name, array $sizes) : MemoryCellPointer
	{
		if (isset($this->stack[$name->value()]))
		{
			throw new CompileError("array '{$name->value()}' is already defined", $name);
		}

		$address = $this->startPosition() + $this->lastIndex() * self::CELL_SIZE; // todo check size
		$relativeAddress = $this->lastIndex();

		$cell = new MemoryCellPointer($address, $name->value(), $type, $relativeAddress, $sizes);

		$this->commentArray($address, $name->value(), $sizes, $cell->plainSize());

		return $this->stack[$name->value()] = $cell;
	}

	public function get(Lexeme $name) : MemoryCellPointer
	{
		if (!isset($this->stack[$name->value()]))
		{
			throw new CompileError("array '{$name->value()}' not defined", $name);
		}

		return $this->stack[$name->value()];
	}

	public function has(Lexeme $name) : bool
	{
		return isset($this->stack[$name->value()]);
	}

	public function failIfHas(Lexeme $name) : void
	{
		if ($this->has($name))
		{
			throw new CompileError("array '{$name->value()}' is already defined", $name);
		}
	}

	protected function commentIndexes() : void
	{
		$this->stream->memoryComment($this->offset, "adr_s");
		$this->stream->memoryComment($this->offset + 1, "dummy");
		for ($i = 0; $i < $this->size; $i++)
		{
			$this->stream->memoryComment(
				$this->offset + self::CELL_SIZE + (self::CELL_SIZE * $i),
				"i$i"
			);
		}
	}

	protected function commentArray(int $address, string $varName, array $sizes, int $plainSize) : void
	{
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
		foreach ($this->stack as $item)
		{
			$result += $item->plainSize();
		}

		return $result;
	}

	protected function startPosition() : int
	{
		return $this->offset + self::CELL_SIZE;
	}
}