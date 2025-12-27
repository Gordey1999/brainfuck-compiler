<?php

namespace Gordy\Brainfuck\BigBrain\Parser;

use Gordy\Brainfuck\BigBrain\Exception\ParseError;

class CommandGrouper
{
	/** @param Lexeme[] $words */
	public static function groupScopes(array $words) : LexemeScope
	{
		return new LexemeScope('', self::groupScopesRecursive($words), [ 0, 0 ]);
	}

	/** @param Lexeme[] $words */
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

					$result[] = new LexemeScope(
						$scopeStart->value(),
						self::groupScopesRecursive($scopeWords),
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

	public static function groupCommands(LexemeScope $words) : LexemeScope
	{
		return new LexemeScope('', self::groupCommandsRecursive($words->children()), [ 0, 0 ]);
	}

	/** @param Lexeme[] $words */
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

			if ($word instanceof LexemeScope && $word->value() === '{')
			{
				$group[] = new LexemeScope(
					'structure',
					self::groupCommandsRecursive($word->children()),
					$word->position(),
				);
				if (empty($group()))
				{
					$result[] = $group[0];
				}
				else
				{
					$result[] = new LexemeScope('', $group, $groupStart->position());
				}
				$group = [];
			}
			else if ($word->value() === ';')
			{
				$result[] = new LexemeScope(';', $group, $groupStart->position());
				$group = [];
			}
			else
			{
				$group[] = $word;
			}
		}

		return $result;
	}
}