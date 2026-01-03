<?php

namespace Gordy\Brainfuck\BigBrain;

class Environment
{
	public function __construct(
		protected Processor $processor,
		protected OutputStream $stream,
		protected Stack $stack,
		protected Memory $memory,
		protected ArraysMemory $arraysMemory,
		protected ArraysProcessor $arraysProcessor
	)
	{
	}

	public function processor() : Processor
	{
		return $this->processor;
	}

	public function stream() : OutputStream
	{
		return $this->stream;
	}

	public function memory() : Memory
	{
		return $this->memory;
	}

	public function arraysMemory() : ArraysMemory
	{
		return $this->arraysMemory;
	}

	public function arraysProcessor() : ArraysProcessor
	{
		return $this->arraysProcessor;
	}

	public function stack() : Stack
	{
		return $this->stack;
	}

	public static function makeForPrecompile(int $registrySize, int $memorySize, int $arraysMemorySize) : self
	{
		$mOffset = $registrySize;
		$amOffset = $registrySize + $memorySize;
		$stream = new OutputStream();
		$stack = new Stack();

		$processor = new Precompile\Processor($stream, $registrySize);
		$memory = new Precompile\Memory($stack, $stream, $mOffset);
		$arraysMemory = new Precompile\ArraysMemory($stack, $stream, $amOffset, $arraysMemorySize);

		$arraysProcessor = new ArraysProcessor($processor, $stream, $amOffset);
		return new self($processor, $stream, $stack, $memory, $arraysMemory, $arraysProcessor);
	}

	public static function makeForRelease(int $registrySize, int $memorySize, int $arraysMemorySize) : self
	{
		$mOffset = $registrySize;
		$amOffset = $registrySize + $memorySize;
		$stream = new OutputStream();
		$stack = new Stack();

		$processor = new Processor($stream, $registrySize);
		$memory = new Memory($stack, $stream, $mOffset);
		$arraysMemory = new ArraysMemory($stack, $stream, $amOffset, $arraysMemorySize);

		$arraysProcessor = new ArraysProcessor($processor, $stream, $amOffset);
		return new self($processor, $stream, $stack, $memory, $arraysMemory, $arraysProcessor);
	}
}