<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Utils\Encoder;

class ArraysProcessor
{
	public const int CELL_SIZE = 2;

	protected Processor $processor;
	protected OutputStream $stream;
	protected int $offset;
	protected bool $uglify;

	public function __construct(Processor $processor, OutputStream $stream, int $offset, bool $uglify)
	{
		$this->processor = $processor;
		$this->stream = $stream;
		$this->offset = $offset;
		$this->uglify = $uglify;
	}

	protected function initCell() : MemoryCell
	{
		return new MemoryCell($this->offset, 'adr_s');
	}

	public function dummyCell() : MemoryCell
	{
		return new MemoryCell($this->offset + 1, 'adr_d');
	}

	public function startCell() : MemoryCell
	{
		return new MemoryCell($this->offset + self::CELL_SIZE, 'i0');
	}

	public function carryCell() : MemoryCell
	{
		return new MemoryCell($this->offset + 2 * self::CELL_SIZE, 'i1');
	}

	public function initIndex(MemoryCell $index) : void
	{
		if ($index instanceof MemoryCellArray)
		{
			$this->stream->startGroup("init pointer with $index");
			$this->processor->addConstant($this->startCell(), $index->startIndex());
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
			$this->processor->move($index, $this->startCell());
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

	public function addConstant(MemoryCell $index, int $value) : void
	{
		$this->goto($index, function() use ($value) {
			$this->addConstantToCurrent($value);
		});
	}

	public function print(MemoryCell $index) : void
	{
		$this->goto($index, function() {
			$this->stream->write('.', 'print value');
		});
	}

	public function input(MemoryCell $index) : void
	{
		$this->goto($index, function() {
			$this->stream->write(',', 'input value');
		});
	}

	public function printString(MemoryCell $pointer, int $size) : void
	{
		$this->walk($pointer, $size, function() use (&$values) {
			$this->stream->write('.');
		}, 'print array');
	}

	public function inputString(MemoryCell $pointer) : void
	{
		$this->gotoIndex($pointer, function() {
			$this->stream->write('+[>>>>,----------[++++++++++<<<[-]>>>[-<<<+>>>]<<+>>]<<]<<', "input until enter");
		});
	}

	public function fill(MemoryCell $pointer, array $values) : void
	{
		$this->walk($pointer, count($values), function() use (&$values) {
			$value = array_shift($values);
			$this->setCurrentByConstant($value);
		});
	}

	public function set(MemoryCell $index) : void
	{
		$this->gotoMove($index, function() {
			$this->stream->write('[-]>[-<+>]<', 'set value');
		});
	}

	public function add(MemoryCell $index) : void
	{
		$this->gotoMove($index, function() {
			$this->stream->write('>[-<+>]<', 'add to value');
		});
	}

	public function sub(MemoryCell $index) : void
	{
		$this->gotoMove($index, function() {
			$this->stream->write('>[-<->]<', 'sub from value');
		});
	}

	protected function goto(MemoryCell $index, callable $callback) : void
	{
		$this->initIndex($index);
		$this->stream->write('[[->>+<<]+>>-]+>', 'goto target index');

		$callback();

		$this->stream->write('<[-<<]', 'return to start');
		$this->processor->setPointer($this->initCell());
	}

	protected function gotoIndex(MemoryCell $index, callable $callback) : void
	{
		$this->initIndex($index);
		$this->stream->write('[[->>+<<]+>>-]', 'goto target index');

		$callback();

		$this->stream->write('[-<<]', 'return to start');
		$this->processor->setPointer($this->initCell());
	}

	protected function gotoMove(MemoryCell $index, callable $callback) : void
	{
		$this->initIndex($index);
		$this->stream->write('[>>[->>+<<]<<[->>+<<]+>>-]+>', 'move value to target index');

		$callback();

		$this->stream->write('<[-<<]', 'return to start');
		$this->processor->setPointer($this->initCell());
	}

	protected function walk(MemoryCell $index, int $count, callable $callback, string $groupComment = null) : void
	{
		$this->goto($index, function() use ($count, $callback, $groupComment) {
			if ($groupComment !== null)
			{
				$this->stream->startGroup($groupComment);
			}
			for ($i = 0; $i < $count; $i++)
			{
				$callback();
				if ($i < $count - 1)
				{
					$this->stream->write('>+>', 'goto next');
				}
			}
			if ($groupComment !== null)
			{
				$this->stream->endGroup();
			}
		});
	}

	protected function setCurrentByConstant(int $value) : void
	{
		$this->stream->write('[-]', 'unset value');
		$this->addConstantToCurrent($value);
	}

	protected function addConstantToCurrent(int $value) : void
	{
		$shortValue = Utils\ModuloHelper::normalizeConstant($value);

		if ($this->uglify && abs($shortValue) > 15)
		{
			[$a, $b, $c] = Utils\NumbersHelper::factorize(abs($shortValue));
			$c = $shortValue > 0 ? $c : -$c;

			$code = sprintf('>%s[-<%s>]<%s',
				Encoder::plus($a),
				$shortValue > 0 ? Encoder::plus($b) : Encoder::minus($b),
			    $c > 0 ? Encoder::plus($c) : Encoder::minus(-$c)
			);
		}
		else
		{
			$code = $shortValue > 0 ? Encoder::plus($shortValue) : Encoder::minus(-$shortValue);
		}

		if ($value > 0)
		{
			$this->stream->write($code, "add `$value` to current");
		}
		else
		{
			$value = -$value;
			$this->stream->write($code, "sub `$value` from current");
		}
	}
}