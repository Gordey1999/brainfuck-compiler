<?php

namespace Gordy\Brainfuck\BigBrain\Term;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Parser\Token;

class Scope implements Term
{
	use HasToken;

	/** @var Term[] */
	protected array $terms;

	public function __construct(array $terms, Token $token = null)
	{
		$this->terms = $terms;
		$this->token = $token;
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