<?php

namespace Gordy\Brainfuck\Compiler\Builder;

use Gordy\Brainfuck\Compiler\Exception\SyntaxError;
use Gordy\Brainfuck\Compiler\Term;

class Command
{
	public const string CONST = 'const';
	public const string ECHO = 'echo';

	public static function build(array $parts) : Term\Command
	{
		$first = reset($parts);
		$other = array_slice($parts, 1, null, true);
		$indexes = array_keys($parts);

		if ($first === self::CONST)
		{
			self::buildConst($other);
		}
		else if ($first === self::ECHO)
		{

		}

		throw new SyntaxError("Undefined command $first", $indexes[0]);
	}

	public static function buildConst(array $parts) : Term\Command
	{
		$words = array_values($parts);
		$indexes = array_keys($parts);

		$name = self::parseName($words[0], $indexes[0]);

		$operator = $words[1];
		if ($operator !== '=')
		{
			throw new \Exception("unexpected operator $operator", $indexes[1]);
		}

		return new Term\Command\DefineConstant(
			$name,
			Expression::build(array_slice($parts, 2, null, true))
		);
	}

	public static function parseName(string $name, int $index) : string
	{
		if (!preg_match("/^[\$a-zA-Z_][\$a-zA-Z0-9_]*$/", $name))
		{
			throw new SyntaxError("name expected", $index);
		}
		return $name;
	}
}