<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

class Lexeme
{
	protected mixed $value;
	protected array $position;

	public function __construct(string $value, array $position)
	{
		$this->value = $value;
		$this->position = $position;
	}

	public function value() : mixed
	{
		return $this->value;
	}

	public function position(): array
	{
		return $this->position;
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