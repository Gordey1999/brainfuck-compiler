<?php

namespace Gordy\Brainfuck\Compiler\Parser;

use Gordy;

class WordSplitter
{
	public static function parse(string $code) : array
	{
		return array_filter(self::split($code));
	}
	protected static function split(string $code) : array
	{
		$oneCharWords = ['{', '}', '[', ']', '(', ')', ',', ';'];
		$operatorChars = ['.', '=', '+', '-', '*', '/', '%', '!', '<', '>'];
		$result = [];

		$inScope = false;
		$scopeChar = null;
		$word = [];
		$wordIndex = 0;
		$wordEnd = false;
		$inComment = false;
		$inName = false;
		$inOperator = false;

		foreach (str_split($code) as $index => $char)
		{
			if ($char === "\n")
			{
				if (!$inScope)
				{
					$inName = false;
					$inOperator = false;
				}
				$inComment = false;
				continue;
			}
			else if ($inComment)
			{
				continue;
			}
			else if ($char === '"' || $char === "'")
			{
				$inName = false;
				$inOperator = false;

				if ($inScope)
				{
					if ($scopeChar === $char)
					{
						$inScope = false;
					}
				}
				else
				{
					$inScope = true;
					$scopeChar = $char;
					$wordEnd = true;
				}
			}
			else if ($inScope)
			{
				// do nothing. will be added to word
			}
			else if ($char === '#')
			{
				$inComment = true;
				continue;
			}
			else if ($char === ' ' || $char === "\t")
			{
				$inName = false;
				$inOperator = false;
				continue;
			}
			else if (preg_match('/[$a-zA-Z0-9_]/', $char))
			{
				if (!$inName)
				{
					$inOperator = false;
					$inName = true;
					$wordEnd = true;
				}
			}
			else if (in_array($char, $operatorChars))
			{
				if (!$inOperator)
				{
					$inName = false;
					$inOperator = true;
					$wordEnd = true;
				}
			}
			else if (in_array($char, $oneCharWords))
			{
				$inName = false;
				$inOperator = false;
				$wordEnd = true;
			}
			else
			{
				continue;
			}

			if ($wordEnd)
			{
				$result[$wordIndex] = self::toLower(implode('', $word));
				$word = [];
				$wordEnd = false;
				$wordIndex = $index + 1;
			}

			$word[] = $char;
		}

		$result[$wordIndex + 1] = implode('', $word);

		return $result;
	}

	public static function toLower(string $word) : string
	{
		if (empty($word)) { return $word; }
		if ($word[0] === '"' || $word[0] === "'") { return $word; }

		return strtolower($word);
	}
}
