<?php

namespace Gordy\Brainfuck\BigBrain\Utils;

class NumbersHelper
{
	protected static ?array $map = null;

	public static function factorize(int $number) : array
	{
		$map = self::getMap();
		return $map[$number] ?? throw new \Exception("unexpected $number");
	}

	protected static function getMap() : array
	{
		if (self::$map === null)
		{
			$result = [];
			for ($search = 16; $search <= 128; $search++)
			{
				$min = [];
				$minCount = 100000;
				for ($i = 1; $i < 32; $i++)
				{
					for ($j = 1; $j < 32; $j++)
					{
						$m = $i * $j;
						$distance = abs($search - $m);
						$count = $i + $j + $distance;
						if ($count < $minCount)
						{
							$min = [$i, $j, $search - $m];
							$minCount = $count;
						}
					}
				}

				$result[$search] = $min;
			}

			self::$map = $result;
		}

		return self::$map;
	}
}