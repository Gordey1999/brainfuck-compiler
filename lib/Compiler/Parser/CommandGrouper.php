<?php

namespace Gordy\Brainfuck\Compiler\Parser;

use Gordy\Brainfuck\Compiler\Exception\ParseError;

class CommandGrouper
{
	public static function groupScopes(array $words) : array
	{
		$pairs = [
			'}' => '{',
			']' => '[',
			')' => '(',
		];

		$result = [];
		$scopeWords = [];
		$scopeCount = 0;
		$scopeStartChar = 0;
		$scopeStartIndex = 0;

		foreach ($words as $index => $word)
		{
			// todo if ($scopeCount < 0)
			if (in_array($word, array_values($pairs)))
			{
				if ($scopeCount === 0)
				{
					$scopeStartChar = $word;
					$scopeStartIndex = $index;
				}
				else
				{
					$scopeWords[$index] = $word;
				}

				$scopeCount++;
			}
			else if (in_array($word, array_keys($pairs)))
			{
				$scopeCount--;

				if ($scopeCount === 0)
				{
					$pair = $pairs[$word];

					if ($scopeStartChar !== $pair)
					{
						throw new ParseError('unexpected end of scope', $index);
					}

					$result[$pair.$scopeStartIndex] = self::groupScopes($scopeWords);
					$scopeWords = [];
				}
				else
				{
					$scopeWords[$index] = $word;
				}
			}
			else if ($scopeCount > 0)
			{
				$scopeWords[$index] = $word;
			}
			else
			{
				$result[$index] = $word;
			}
		}

		return $result;
	}

	public static function groupCommands(array $words) : array
	{
		$result = [];
		$group = [];
		$groupIndex = null;

		foreach ($words as $key => $word)
		{
			if ($groupIndex === null)
			{
				$groupIndex = is_numeric($key) ? $key : substr($word, 1);
			}

			if (!is_numeric($key) && $key[0] === '{')
			{
				$group[$key] = self::groupCommands($word);
				$result["s$groupIndex"] = $group;
				$group = [];
				$groupIndex = null;
			}
			else if ($word === ';')
			{
				$result[";$groupIndex"] = $group;
				$group = [];
				$groupIndex = null;
			}
			else
			{
				$group[$key] = $word;
			}
		}

		return $result;
	}
}