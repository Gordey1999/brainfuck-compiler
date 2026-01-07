<?php

namespace Gordy\Brainfuck\BigBrain\Utils;

class ModuloHelper
{
	public static function normalizeConstant(int $value) : int
	{
		if ($value < 0)
		{
			return -self::normalizeConstant(-$value);
		}
		$value = $value % 256;
		if ($value > 128)
		{
			$value = -(256 - $value);
		}
		return $value;
	}
}