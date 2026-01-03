<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

class Names
{
	public const string VARIABLE = 'variable';
	public const string ARRAY = 'array';
	public const string FUNCTION = 'function';
	public const string CLASS = 'class';
	public const string OBJECT = 'object';

	protected array $stack = [];

	public function define(string $name, string $type) : void
	{
		$this->stack[] = [ $name, $type ];
	}

	public function getType(string $name) : string
	{
		return $name;
	}

	public function scopeStart() : void
	{

	}

	public function scopeEnd() : void
	{

	}
}