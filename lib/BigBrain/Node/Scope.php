<?php

namespace Gordy\Brainfuck\BigBrain\Node;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Token;

class Scope implements Node
{
	/** @var Node[] */
	protected array $nodes;

	public function __construct(array $nodes)
	{
		$this->nodes = $nodes;
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

	public function empty() : bool
	{
		return empty($this->nodes);
	}

	public function __toString() : string
	{
		return '';
	}

	public function token() : Token
	{
		throw new CompileError('something went wrong', new Token(''));
	}
}