<?php

namespace Gordy\Brainfuck\BigBrain;


class Parser
{
	protected Builder\Names $names;

	public function __construct()
	{
		$this->names = new Builder\Names();
	}

	public function parse(string $code) : Term\Term
	{
		$tokens = Parser\TokenSplitter::parse($code);

		$tokenTree = Parser\CommandGrouper::groupScopes($tokens);
		$tokenTree = Parser\CommandGrouper::groupCommands($tokenTree);

		return (new Builder\StructureBuilder($this->names))->buildScope($tokenTree);
	}
}
