<?php

namespace Gordy\Brainfuck\BigBrain\Term;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;

class Scope implements Term
{
	use HasLexeme;

	/** @var Term[] */
	protected array $terms;

	public function __construct(array $terms, Lexeme $lexeme = null)
	{
		$this->terms = $terms;
		$this->lexeme = $lexeme;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		$env->stack()->newScope();
		foreach ($this->terms as $term)
		{
			$term->compile($env);
		}
		$env->stack()->dropScope();
	}

	public function __toString() : string
	{
		return '';
	}
}