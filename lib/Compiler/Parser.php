<?php

namespace Gordy\Brainfuck\Compiler;

use Gordy\Brainfuck\Compiler\Exception\SyntaxError;

class Parser
{
	public function __construct()
	{
		// todo error 'undefined sequence'
	}

	public function parse(string $code) : Term\Term
	{
		$words = Parser\WordSplitter::parse($code);

		$words = Parser\CommandGrouper::groupScopes($words);
		$commands = Parser\CommandGrouper::groupCommands($words);

		return $this->buildScope($commands);
	}

	public function buildScope(array $commands) : Term\Scope
	{
		$result = [];

		foreach ($commands as $key => $command)
		{
			if (is_numeric($key))
			{
				throw new \Exception("unexpected error");
			}
			else if ($key[0] === ';')
			{
				$result[] = Builder\Command::build($command);
			}
			else if ($key[0] === 's')
			{
				$result[] = $this->buildStructure($command);
			}
			else
			{
				throw new \Exception("unexpected error");
			}
		}

		return new Term\Scope($result);
	}

	public function buildStructure(array $structure) : Term\Structure
	{
		throw new \Exception("not supported yet");
	}
}
