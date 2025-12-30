<?php

namespace Gordy\Brainfuck\BigBrain\Utils;

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
		if ($length > 5)
		{
			$chunks = str_split(str_repeat($char, $length), 5);
			$chunks = array_chunk($chunks, 5);

			return implode(PHP_EOL, array_map(function($chunk) use($length) {
				return implode(' ', $chunk);
			}, $chunks));
		}

		return str_repeat($char, $length);
	}
}