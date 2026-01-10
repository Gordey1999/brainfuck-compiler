<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

use Gordy\Brainfuck\BigBrain\Exception\ParseError;

class CommandGrouper
{
	/** @param Token[] $words */
	public static function groupScopes(array $words) : TokenScope
	{
		return new TokenScope('', self::groupScopesRecursive($words));
	}

	/** @param Token[] $words */
	public static function groupScopesRecursive(array $words) : array
	{
		$pairs = [
			'}' => '{',
			']' => '[',
			')' => '(',
		];

		$result = [];
		$scopeWords = [];
		$scopeCount = 0;
		$scopeStart = 0;

		foreach ($words as $word)
		{
			// todo if ($scopeCount < 0)
			if (in_array($word->value(), array_values($pairs)))
			{
				if ($scopeCount === 0)
				{
					$scopeStart = $word;
				}
				else
				{
					$scopeWords[] = $word;
				}

				$scopeCount++;
			}
			else if (in_array($word->value(), array_keys($pairs)))
			{
				$scopeCount--;

				if ($scopeCount === 0)
				{
					$pair = $pairs[$word->value()];

					if ($scopeStart->value() !== $pair)
					{
						throw new ParseError('unexpected end of scope', $word);
					}

					$result[] = new TokenScope(
						$scopeStart->value(),
						self::groupScopesRecursive($scopeWords),
						$scopeStart->index(),
						$scopeStart->position(),
					);

					$scopeWords = [];
				}
				else
				{
					$scopeWords[] = $word;
				}
			}
			else if ($scopeCount > 0)
			{
				$scopeWords[] = $word;
			}
			else
			{
				$result[] = $word;
			}
		}

		return $result;
	}

	public static function groupCommands(TokenScope $words) : TokenScope
	{
		return new TokenScope('', self::groupCommandsRecursive($words->children()));
	}

	/** @param Token[] $words */
	public static function groupCommandsRecursive(array $words) : array
	{
		$result = [];
		$group = [];
		$groupStart = null;

		foreach ($words as $word)
		{
			if ($groupStart === null)
			{
				$groupStart = $word;
			}

			if ($word instanceof TokenScope && $word->value() === '{')
			{
				$group[] = new TokenScope(
					$word->value(),
					self::groupCommandsRecursive($word->children()),
					$word->index(),
					$word->position(),
				);
				$result[] = new TokenScope(
					';',
					$group,
					$groupStart->index(),
					$groupStart->position()
				);
				$group = [];
				$groupStart = null;
			}
			else if ($word->value() === ';')
			{
				$result[] = new TokenScope(
					';',
					$group,
					$groupStart->index(),
					$groupStart->position()
				);
				$group = [];
				$groupStart = null;
			}
			else
			{
				$group[] = $word;
			}
		}

		if (!empty($group))
		{
			$last = array_pop($group);
			throw new ParseError('";" expected', $last);
		}

		return $result;
	}
}