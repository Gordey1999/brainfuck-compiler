<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

class Names
{
	public const string VARIABLE = 'variable';
	public const string ARRAY = 'array';

	protected array $stack = [];

	public function remember(string $name, string $type) : void
	{
		$this->stack[$name] = $type;
	}

	public function getType(string $name) : string
	{
		return $this->stack[$name] ?? self::VARIABLE;
	}

	public function isVariable(string $name) : bool
	{
		return $this->getType($name) === self::VARIABLE;
	}

	public function isArray(string $name) : bool
	{
		return $this->getType($name) === self::ARRAY;
	}

	public function scopeStart() : void
	{

	}

	public function scopeEnd() : void
	{

	}
}