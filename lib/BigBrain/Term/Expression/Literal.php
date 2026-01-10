<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain\Utils;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;

class Literal implements Expression
{
	use Term\HasToken;

	public function __construct(Token $token)
	{
		$this->token = $token;
	}

	public function compile(Environment $env) : void
	{
		// do nothing
	}

	public function resultType(Environment $env) : Type\Computable
	{
		$value = $this->token->value();
		$parsed = match(true) {
			$value[0] === '"' || $value[0] === "'" => Utils\CharHelper::convertSpecialChars(
				substr($value, 1, -1)
			),
			$value === 'true' || $value === 'false' => $value === 'true',
			$value === 'eol' => "\n",
			ctype_digit($value) => (int)$value,
			default => throw new CompileError('not supported type', $value),
		};

		return new Type\Computable($parsed);
	}

	public function compileCalculation(Environment $env, MemoryCell $result) : void
	{
		$env->processor()->addConstant($result, $this->resultType($env)->getNumeric());
	}

	public function hasVariable(string $name) : bool
	{
		return false;
	}

	public function __toString() : string
	{
		return $this->token()->value();
	}
}