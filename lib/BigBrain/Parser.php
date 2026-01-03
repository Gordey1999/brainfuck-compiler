<?php

namespace Gordy\Brainfuck\BigBrain;

use Gordy\Brainfuck\BigBrain\Builder\CommandBuilder;
use Gordy\Brainfuck\BigBrain\Parser\LexemeScope;

class Parser
{
	protected Builder\Names $names;
	protected CommandBuilder $command;

	public function __construct()
	{
		$this->names = new Builder\Names();
		$this->command = new Builder\CommandBuilder($this->names);
	}

	public function parse(string $code) : Term\Term
	{
		$lexemes = Parser\WordSplitter::parse($code);

		$lexemeTree = Parser\CommandGrouper::groupScopes($lexemes);
		$lexemeTree = Parser\CommandGrouper::groupCommands($lexemeTree);

		return $this->buildScope($lexemeTree);
	}

	public function buildScope(LexemeScope $lexemeTree) : Term\Scope
	{
		$result = [];

		foreach ($lexemeTree->children() as $block)
		{
			if (!$block instanceof LexemeScope)
			{
				throw new \Exception('unexpected error');
			}
			if ($block->isCommand())
			{
				$result[] = $this->command->build($block);
			}
			else if ($block->isStructure())
			{
				$result[] = $this->buildStructure($block);
			}
			else
			{
				throw new \Exception("unexpected error");
			}
		}

		return new Term\Scope($result, $lexemeTree);
	}

	public function buildStructure(LexemeScope $structure) : Term\Structure
	{
		throw new \Exception("not supported yet");
	}
}
