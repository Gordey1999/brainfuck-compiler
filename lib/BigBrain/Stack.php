<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class Stack
{
	/** @var array<int, <string, object>> */
	private array $stack = [];

	protected OutputStream $stream;

	/**
	 * @template T
	 * @param Lexeme $name
	 * @param T $value
	 * @return T
	 */
	public function push(Lexeme $name, mixed $value) : mixed
	{
		if ($this->search($name->value()) !== null)
		{
			throw new CompileError("variable '{$name->value()}' is already defined", $name);
		}

		return $this->stack[0][$name->value()] = $value;
	}

	/**
	 * @template T
	 * @param Lexeme $name
	 * @param ?class-string<T> $type
	 * @return T
	 */
	public function get(Lexeme $name, string $type = null) : object
	{
		$item = $this->search($name->value())
			?? throw new CompileError("variable '{$name->value()}' not defined", $name);

		if ($type !== null && !$item instanceof $type)
		{
			throw new CompileError('stored value not compatible', $name);
		}

		return $item;
	}

	/**
	 * @template T of object
	 * @param ?class-string<T> $type
	 * @return array<string, T>
	 */
	public function getAll(string $type = null) : array
	{
		$result = [];
		foreach (array_reverse($this->stack) as $scope)
		{
			foreach ($scope as $name => $value)
			{
				if ($type === null || $value instanceof $type)
				{
					$result[$name] = $value;
				}
			}
		}
		return $result;
	}

	protected function search(string $name) : ?object
	{
		foreach ($this->stack as $scope)
		{
			if (isset($scope[$name]))
			{
				return $scope[$name];
			}
		}
		return null;
	}

	public function newScope() : void
	{
		array_unshift($this->stack, []);
	}

	public function dropScope() : void
	{
		if (count($this->stack) === 1)
		{
			throw new \Exception('stack is empty');
		}
		array_shift($this->stack);
	}
}