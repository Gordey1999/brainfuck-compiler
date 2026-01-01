<?php

namespace Gordy\Brainfuck\BigBrain\Type;

use Gordy\Brainfuck\BigBrain\Utils;

class Computable implements Type
{
	public const string STRING = 'string';
	public const string CHAR = 'char';
	public const INTEGER = 'integer';
	public const BOOLEAN = 'boolean';
	public const FLOAT = 'float';
	public const ARRAY = 'array';
	public const NULL = 'null';

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

	public function numericNullableCompatible() : bool
	{
		return $this->type === self::NULL
			|| $this->numericCompatible();
	}

	public function arrayCompatible() : bool
	{
		return $this->type === self::ARRAY
			|| $this->type === self::STRING;
	}

	public function getNumeric() : int
	{
		return match ($this->type) {
			self::INTEGER => $this->value,
			self::BOOLEAN => (int)$this->value,
			self::FLOAT => (float)$this->value,
			self::CHAR => Utils\CharHelper::charToNumber($this->value),
			default => throw new \Exception('not compatible'),
		};
	}

	public function getNumericNullable() : ?int
	{
		if ($this->type === self::NULL) { return null; }
		return $this->getNumeric();
	}

	public function getString() : string
	{
		return match ($this->type) {
			self::BOOLEAN => $this->value ? '1': '0',
			self::STRING, self::CHAR => $this->value,
			default => (string)$this->value,
		};
	}

	public function getArray() : array
	{
		return match ($this->type) {
			self::ARRAY => $this->value,
			self::STRING => Utils\CharHelper::stringToBytes($this->value),
			default => throw new \Exception('not compatible'),
		};
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
			is_null($value)  => self::NULL,
			default => throw new \Exception('type not supported'),
		};
	}
}