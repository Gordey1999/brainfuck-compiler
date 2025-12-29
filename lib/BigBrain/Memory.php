<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class Memory
{
	private array $stack = [];
	protected $offset;

	public function __construct(int $offset)
	{
		$this->offset = $offset;
	}

	private function addScope() : void
	{
		$this->stack[] = [];
	}

	public function dropScope() : void
	{
		array_pop($this->stack);
	}

	public function allocate(Type\BaseType $type, Lexeme $name) : int
	{
		if (isset($this->stack[$name->value()]))
		{
			throw new CompileError("variable '{$name->value()}' is already defined", $name);
		}
		$address = count($this->stack) + $this->offset;

		$this->stack[$name->value()] = [
			'type' => $type,
			'address' => $address,
		];

		return $address;
	}

	public function address(Lexeme $name) : int
	{
		if (!isset($this->stack[$name->value()]))
		{
			throw new CompileError("variable '{$name->value()}' not defined", $name);
		}

		return $this->stack[$name->value()]['address'];
	}

	public function type(Lexeme $name) : Type\BaseType
	{
		if (!isset($this->stack[$name->value()]))
		{
			throw new CompileError("variable '{$name->value()}' not defined", $name);
		}

		return $this->stack[$name->value()]['type'];
	}
}