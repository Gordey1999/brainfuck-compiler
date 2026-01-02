<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Utils\Encoder;

class ArraysProcessor
{
	public const int CELL_SIZE = 2;

	protected Processor $processor;
	protected OutputStream $stream;
	protected int $offset;

	public function __construct(Processor $processor, OutputStream $stream, int $offset)
	{
		$this->processor = $processor;
		$this->stream = $stream;
		$this->offset = $offset;
	}

	protected function initCell() : MemoryCell
	{
		return new MemoryCell($this->offset, 'adr_s');
	}

	public function startCell() : MemoryCell
	{
		return new MemoryCell($this->offset + self::CELL_SIZE, 'i0');
	}

	public function initIndex(MemoryCell $index) : void
	{
		if ($index instanceof MemoryCellArray)
		{
			$this->stream->startGroup("init pointer with $index");
			$this->processor->addConstant($this->startCell(), $index->relativeAddress());
			$this->processor->goto($this->startCell());
			$this->stream->endGroup();
			return;
		}
		if ($index->address() === $this->startCell()->address())
		{
			$this->processor->goto($this->startCell());
		}
		else
		{
			$this->stream->startGroup("init pointer with $index");
			$this->processor->moveNumber($index, $this->startCell());
			$this->processor->goto($this->startCell());
			$this->stream->endGroup();
		}
	}

	public function get(MemoryCell $index) : MemoryCell
	{
		$this->initIndex($index);
		$this->stream->write('[[->>+<<]+>>-]>', 'goto pointer');
		$this->stream->write('[-<+>>+<]<[->+<]+', 'copy carry');
		$this->stream->write('[->>[-<<+>>]<<<<]', 'move carry');
		$this->processor->setPointer($this->initCell());

		return $this->startCell();
	}

	public function setConstant(MemoryCell $index, int $value) : void
	{
		$this->goto($index, function() use ($value) {
			$this->setCurrentByConstant($value);
		});
	}

	public function print(MemoryCell $index) : void
	{
		$this->goto($index, function() {
			$this->stream->write('.', 'printValue');
		});
	}

	public function fill(MemoryCell $pointer, array $values) : void
	{
		$this->walk($pointer, count($values), function() use (&$values) {
			$value = array_shift($values);
			$this->setCurrentByConstant($value);
		});
	}

	public function set(MemoryCell $index, MemoryCell $value) : void
	{
		// todo
	}

	protected function goto(MemoryCell $index, callable $callback) : void
	{
		$this->initIndex($index);
		$this->stream->write('[[->>+<<]+>>-]+>', 'goto pointer');

		$callback();

		$this->stream->write('<[-<<]', 'return to start');
		$this->processor->setPointer($this->initCell());
	}

	protected function walk(MemoryCell $index, int $count, callable $callback) : void
	{
		$this->goto($index, function() use ($count, $callback) {
			for ($i = 0; $i < $count; $i++)
			{
				$callback();
				if ($i < $count - 1)
				{
					$this->stream->write('>+>', 'goto next');
				}
			}
		});
	}

	protected function setCurrentByConstant(int $value) : void
	{
		$shortValue = Utils\ModuloHelper::normalizeConstant($value);
		$valueStr = $shortValue > 0 ? Encoder::plus($shortValue) : Encoder::minus(-$shortValue);

		$this->stream->write("[-]$valueStr", "set value `$value`");
	}
}