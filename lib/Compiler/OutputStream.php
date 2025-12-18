<?php

namespace Gordy\Brainfuck\Compiler;

class OutputStream
{
	private array $stream = [];
	private array $comment = [];

	private bool $inGroup = false;
	private string $groupComment;

	protected const int CODE_WIDTH = 30;
	protected const int INDENT_WIDTH = 2;

	public function startGroup(string $comment) : void
	{
		if ($this->inGroup) { return; }
		$this->inGroup = true;
		$this->groupComment = $comment;
	}

	public function endGroup() : void
	{
		$this->inGroup = false;
	}

	public function write(string $code, string $comment = '') : void
	{
		if (empty(trim($code))) { return; }

		if ($this->inGroup)
		{
			$comment = '';
			if ($this->groupComment)
			{
				$comment = $this->groupComment;
				$this->groupComment = '';
			}
		}

		$this->stream[] = $code;
		$this->comment[] = $comment;
	}

	public function build() : string
	{
		$lines = [];
		$indentCount = 0;

		foreach ($this->stream as $key => $code)
		{
			if ($code === '[')
			{
				$indent = str_repeat('  ', $indentCount);
				$code = $indent . $code;
				$comment = sprintf('%s# %s', $indent, $this->comment[$key]);
				array_push($lines, '', $comment, $code);

				$indentCount++;
			}
			else if ($code === ']')
			{
				$indentCount--;

				$indent = str_repeat('  ', $indentCount);
				$code = $indent . $code;
				$lines[] = $code;
			}
			else
			{
				$parts = explode(PHP_EOL, $code);
				$parts = array_filter($parts, fn($part) => $part !== '');

				if (empty($parts)) { continue; }

				$indent = str_repeat('  ', $indentCount);
				$parts = array_map(static function ($part) use ($indent) {
					return $indent . $part;
				}, $parts);

				if ($this->comment[$key])
				{
					$first = array_shift($parts);
					$lines[] = sprintf('%-30s # %s', $first, $this->comment[$key]);
				}
				array_push($lines, ...$parts);
			}
		}

		return implode(PHP_EOL, $lines);
	}
}
