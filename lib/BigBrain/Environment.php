<?php

namespace Gordy\Brainfuck\BigBrain;

class Environment
{
	protected Processor $processor;
	protected OutputStream $stream;
	protected Memory $memory;

	public function __construct(Processor $processor, OutputStream $stream, Memory $memory)
	{
		$this->processor = $processor;
		$this->stream = $stream;
		$this->memory = $memory;
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
}