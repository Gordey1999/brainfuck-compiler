<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

class LexemeScope extends Lexeme
{
	protected array $children;

	public function __construct($value, array $children, int $index = 0, array $position = [0, 0])
	{
		parent::__construct($value, $index, $position);
		$this->children = $children;
	}

	public function hasChildren() : bool
	{
		return !empty($this->children);
	}

	/** @return self[] */
	public function children() : array
	{
		return $this->children;
	}

	public function isSingle() : bool
	{
		return count($this->children) === 1;
	}

	public function first() : Lexeme
	{
		return $this->children[0] ?? throw new \Exception("empty children");
	}

	public function get(int $index) : Lexeme
	{
		return $this->children[$index] ?? throw new \Exception("no child");
	}

	public function slice(int $start, int $length = null) : LexemeScope
	{
		$parts = array_slice($this->children, $start, $length);
		return new LexemeScope($this->value, $parts, $this->index, $this->position);
	}

	public function isCommand() : bool
	{
		return $this->value === ';';
	}

	public function isStructure() : bool
	{
		return $this->value === 'structure';
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