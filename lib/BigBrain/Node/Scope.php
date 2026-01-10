<?php

namespace Gordy\Brainfuck\BigBrain\Node;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Parser\Token;

class Scope implements Node
{
	use HasToken;

	/** @var Node[] */
	protected array $nodes;

	public function __construct(array $nodes, Token $token = null)
	{
		$this->nodes = $nodes;
		$this->token = $token;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		$env->stack()->newScope();
		foreach ($this->nodes as $node)
		{
			$node->compile($env);
		}
		$env->stack()->dropScope();
	}

	public function __toString() : string
	{
		return '';
	}
}