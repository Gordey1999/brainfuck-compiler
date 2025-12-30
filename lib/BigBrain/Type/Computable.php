<?php

namespace Gordy\Brainfuck\BigBrain\Type;

class Computable implements Type
{
	public const string STRING = 'string';
	public const string CHAR = 'char';
	public const INTEGER = 'integer';
	public const BOOLEAN = 'boolean';
	public const FLOAT = 'float';
	public const ARRAY = 'array';

	protected mixed $value;
	protected string $type;

	public function __construct($value)
	{
		$this->value = $value;
		$this->type = self::valueType($value);
	}

	public function value() : mixed
	{
		return $this->value;
	}

	public function type() : string
	{
		return $this->type;
	}

	public function numericCompatible() : bool
	{
		return $this->type === self::INTEGER
			|| $this->type === self::BOOLEAN
			|| $this->type === self::FLOAT
			|| $this->type === self::CHAR;
	}

	public function getNumeric() : int
	{
		return match ($this->type) {
			self::INTEGER => $this->value,
			self::BOOLEAN => (int)$this->value,
			self::FLOAT => (float)$this->value,
			self::CHAR => self::charToNumber($this->value),
			default => throw new \Exception('not compatible'),
		};
	}

	public function stringCompatible() : bool
	{
		return $this->type === self::STRING || $this->type === self::CHAR;
	}

	public static function valueType(mixed $value) : string
	{
		return match (true) {
			is_string($value) && mb_strlen($value) === 1 => self::CHAR,
			is_string($value) => self::STRING,
			is_array($value) => self::ARRAY,
			is_bool($value) => self::BOOLEAN,
			is_int($value) => self::INTEGER,
			is_float($value)  => self::FLOAT,
			default => 'undefined',
		};
	}

	public static function charToNumber(string $char) : int
	{
		$converted = iconv('UTF-8', 'Windows-1251', $char);
		return ord($converted);
	}

	public static function numberToChar(int $char) : string
	{
		$char = chr($char);
		return iconv('Windows-1251', 'UTF-8', $char);
	}
}