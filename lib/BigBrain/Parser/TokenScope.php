<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

class TokenScope extends Token
{
	protected array $children;

	public function __construct($value, array $children, int $index = 0, array $position = [0, 0])
	{
		parent::__construct($value, $index, $position);
		$this->children = $children;
	}

	public function empty() : bool
	{
		return empty($this->children);
	}

	/** @return self[] */
	public function children() : array
	{
		return $this->children;
	}

	public function count() : int
	{
		return count($this->children);
	}

	public function isSingle() : bool
	{
		return count($this->children) === 1;
	}

	public function first() : Token
	{
		return $this->children[0] ?? throw new \Exception("empty children");
	}

	public function last() : Token
	{
		$length = count($this->children);
		if ($length === 0) { throw new \Exception("empty children"); }
		return $this->children[$length - 1];
	}

	public function get(int $index) : Token
	{
		return $this->children[$index] ?? throw new \Exception("no child");
	}

	public function slice(int $start, int $length = null) : TokenScope
	{
		$parts = array_slice($this->children, $start, $length);
		return new TokenScope($this->value, $parts, $this->index, $this->position);
	}

	public function isBlock() : bool
	{
		return $this->value === ';';
	}

	public function dump() : array
	{
		return [
			'value' =>  $this->value(),
			'children' => array_map(static function($child) {
				return $child->dump();
			}, $this->children())
		];
	}
}