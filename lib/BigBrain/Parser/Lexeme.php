<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

class Lexeme
{
	protected string $value;
	protected array $position;
	protected int $index;

	public function __construct(string $value, int $index = 0, array $position = [0, 0])
	{
		$this->value = $value;
		$this->index = $index;
		$this->position = $position;
	}

	public function value() : string
	{
		return $this->value;
	}

	public function position(): array
	{
		return $this->position;
	}

	public function index() : int
	{
		return $this->index;
	}

	public function isLiteral() : bool
	{
		$keywords = [ 'true', 'false' ];

		return in_array($this->value, $keywords)
			|| ctype_digit($this->value)
			|| $this->value[0] === '"'
			|| $this->value[0] === "'";
	}

	public function isName() : bool
	{
		return preg_match('/^[$a-zA-Z_][$a-zA-Z0-9_]*$/', $this->value);
	}

	public function dump() : array
	{
		return [
			'value' =>  $this->value(),
		];
	}
}