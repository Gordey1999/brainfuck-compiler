<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class Memory
{
	/** @var array<string, MemoryCellTyped> */
	private array $stack = [];
	protected int $offset;

	protected OutputStream $stream;

	public function __construct(OutputStream $stream, int $offset)
	{
		$this->offset = $offset;
		$this->stream = $stream;
	}

	private function addScope() : void
	{
		$this->stack[] = [];
	}

	public function dropScope() : void
	{
		array_pop($this->stack);
	}

	public function allocate(Type\BaseType $type, Lexeme $name) : MemoryCellTyped
	{
		if (isset($this->stack[$name->value()]))
		{
			throw new CompileError("variable '{$name->value()}' is already defined", $name);
		}
		$address = $this->offset + count($this->stack);

		$this->stream->memoryComment($address, $name->value());

		return $this->stack[$name->value()] = new MemoryCellTyped($address, $name->value(), $type);
	}

	public function get(Lexeme $name) : MemoryCellTyped
	{
		if (!isset($this->stack[$name->value()]))
		{
			throw new CompileError("variable '{$name->value()}' not defined", $name);
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
			throw new CompileError("variable '{$name->value()}' is already defined", $name);
		}
	}
}