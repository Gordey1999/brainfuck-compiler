<?php

namespace Gordy\Brainfuck\BigBrain\Utils;

class CharHelper
{
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

	public static function convertSpecialChars(string $string) : string
	{
		return str_replace('\n', "\n", $string);
	}

	public static function stringToBytes(string $string) : array
	{
		$result = [];
		foreach (mb_str_split($string) as $char)
		{
			$result[] = self::charToNumber($char);
		}
		return $result;
	}
}