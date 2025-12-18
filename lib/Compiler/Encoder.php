<?php

namespace Gordy\Brainfuck\Compiler;

class Encoder
{
	public static function goto(int $from, int $to) : string
	{
		if ($from < $to)
		{
			return self::moveForward($to - $from);
		}
		else if ($from > $to)
		{
			return self::moveBack($from - $to);
		}
		return '';
	}

	public static function plus(int $length) : string
	{
		return self::prettyNumber('+', $length);
	}

	public static function minus(int $length) : string
	{
		return self::prettyNumber('-', $length);
	}

	protected static function moveForward(int $length) : string
	{
		return str_repeat('>', $length);
	}

	protected static function moveBack(int $length) : string
	{
		return str_repeat('<', $length);
	}

	protected static function prettyNumber(string $char, int $length) : string
	{
		return chunk_split(str_repeat($char, $length), 10, PHP_EOL);
	}
}