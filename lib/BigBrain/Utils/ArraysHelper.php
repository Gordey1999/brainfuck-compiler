<?php

namespace Gordy\Brainfuck\BigBrain\Utils;

class ArraysHelper
{
	public static function plainArray(array $input, array $dimensions) : array
	{
		$result = [];
		$currentDim = array_shift($dimensions);

		for ($i = 0; $i < $currentDim; $i++) {
			$value = $input[$i] ?? null;

			if (empty($dimensions))
			{
				$result[] = $value ?? 0;
			}
			else
			{
				if (is_array($value))
				{
					$result = array_merge($result, self::plainArray($value, $dimensions));
				}
				else
				{
					$count = array_product($dimensions);
					$values = array_fill(0, $count, $value ?? 0);

					$result = array_merge($result, $values);
				}
			}
		}

		return $result;
	}

	public static function dimensionsCompatible(array $dimensions, array $to) : bool
	{
		if (count($dimensions) !== count($to))
		{
			return false;
		}

		foreach ($dimensions as $key => $size)
		{
			if ($to[$key] !== null && $size > $to[$key])
			{
				return false;
			}
		}

		return true;
	}

	public static function dimensionsUnion(array $a, array $b) : array
	{
		foreach ($b as $key => $size)
		{
			if ($a[$key] === null)
			{
				$a[$key] = $size;
			}
			else if ($size !== null)
			{
				$a[$key] = max($a[$key], $size);
			}
		}
		return $a;
	}

	public static function dimensions(array $array, array &$dimensions = [], int $level = 0) : array
	{
		$currentCount = count($array);

		if (!isset($dimensions[$level]) || $currentCount > $dimensions[$level])
		{
			$dimensions[$level] = $currentCount;
		}

		foreach ($array as $value)
		{
			if (is_array($value))
			{
				self::dimensions($value, $dimensions, $level + 1);
			}
		}

		return $dimensions;
	}

	public static function toBoolArray(array $array) : array
	{
		$result = [];
		foreach ($array as $value)
		{
			$result[] = (bool)$value;
		}

		return $result;
	}

	public static function hasNull(array $array) : bool
	{
		foreach ($array as $value)
		{
			if ($value === null)
			{
				return true;
			}
		}
		return false;
	}

	public static function complexIndex(int $index, array $dimensions) : array
	{
		$indices = [];

		for ($i = count($dimensions) - 1; $i >= 0; $i--) {
			$dimSize = $dimensions[$i];

			$indices[$i] = $index % $dimSize;
			$index = intdiv($index, $dimSize);
		}

		ksort($indices);
		return $indices;
	}

	public static function indexMultipliers(array $sizes) : array
	{
		$last = 1;
		$result = [$last];
		array_shift($sizes);
		foreach (array_reverse($sizes) as $size)
		{
			$result[] = $last * $size;
			$last = $last * $size;
		}

		return array_reverse($result);
	}
}