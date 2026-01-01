<?php

namespace Gordy\Brainfuck\BigBrain\Utils;

class ModuloHelper
{
	public static function normalizeConstant(int $value) : int
	{
		$value = $value % 256;
		if ($value > 128)
		{
			$value = -(256 - $value);
		}
		return $value;
	}
}