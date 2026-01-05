<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Builder\CommandBuilder;
use Gordy\Brainfuck\BigBrain\Parser\LexemeScope;

class Parser
{
	protected Builder\Names $names;

	public function __construct()
	{
		$this->names = new Builder\Names();
	}

	public function parse(string $code) : Term\Term
	{
		$lexemes = Parser\WordSplitter::parse($code);

		$lexemeTree = Parser\CommandGrouper::groupScopes($lexemes);
		$lexemeTree = Parser\CommandGrouper::groupCommands($lexemeTree);

		return (new Builder\StructureBuilder($this->names))->buildScope($lexemeTree);
	}
}
