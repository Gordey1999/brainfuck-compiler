<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

use Gordy;
use Gordy\Brainfuck\BigBrain\Exception\ParseError;

class WordSplitter
{
	/** @return Lexeme[] */
	public static function parse(string $code) : array
	{
		return self::split($code);
	}
	protected static function split(string $code) : array
	{
		$oneCharWords = ['{', '}', '[', ']', '(', ')', ',', ';'];
		$operatorChars = ['.', '=', '+', '-', '*', '/', '%', '!', '<', '>', '|', '&'];
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
				throw new ParseError(
					"unknown token '$char'",
					new Lexeme(
						$char,
						$index,
						self::getPosition($code, $index)
					)
				);
			}

			if ($wordEnd)
			{
				if (!empty($word))
				{
					$result[] = new Lexeme(
						implode('', $word),
						$wordIndex,
						self::getPosition($code, $wordIndex)
					);
				}

				$word = [];
				$wordEnd = false;
				$wordIndex = $index;
			}

			$word[] = $char;
		}

		$result[] = new Lexeme(
			implode('', $word),
			$wordIndex,
			self::getPosition($code, $wordIndex)
		);

		return $result;
	}

	public static function getPosition(string $code, int $index) : array
	{
		$linePos = 0;
		$columnPos = 0;
		for ($i = 0; $i < $index; $i++)
		{
			if ($code[$i] === "\n")
			{
				$linePos++;
				$columnPos = 0;
			}
			else
			{
				$columnPos++;
			}
		}

		return [ $linePos, $columnPos ];
	}
}
