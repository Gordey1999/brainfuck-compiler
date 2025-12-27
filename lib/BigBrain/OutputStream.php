<?php

namespace Gordy\Brainfuck\BigBrain;

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

		if (!empty(trim($comment)) || $code === ']')
		{
			$this->newBlock($code, $comment);

			if ($code === ']')
			{
				$this->tryCollapseScope();
			}
		}
		else
		{
			$this->pushToBlock($code);
		}
	}

	public function blockComment(string $comment) : void
	{
		$this->newBlock('', $comment, true);
	}

	public function build() : string
	{
		$result = [];
		$indentCount = 0;

		foreach ($this->stream as $block)
		{
			if ($block['commentOnly'])
			{
				$result[] = sprintf("\n### %s", $block['comment']);
				continue;
			}
			if (!$block['commentOnly'] && empty($block['code'])) { continue; }

			$firstLine = $block['code'][0];

			if (count($block['code']) === 1 && $firstLine === '[')
			{
				$indent = str_repeat(' ', $indentCount);

				$comment = sprintf('%s# %s', $indent, $block['comment']);
				$code = $indent . $firstLine;

				array_push($result, '', $comment, $code);

				$indentCount += self::INDENT_WIDTH;
			}
			else if (count($block['code']) === 1 && $firstLine === ']')
			{
				$indentCount -= self::INDENT_WIDTH;

				$indent = str_repeat(' ', $indentCount);
				$code = $indent . $firstLine;

				$result[] = $code;
			}
			else
			{
				$indent = str_repeat(' ', $indentCount);

				$lines = $this->combineLines($block['code'], $indent);

				$lines[0] = sprintf('%-30s # %s', $lines[0], $block['comment']);

				array_push($result, ...$lines);
			}
		}

		return implode(PHP_EOL, $result);
	}

	public function buildMin() : string
	{
		$result = [];

		foreach ($this->stream as $block)
		{
			$result[] = preg_replace('/[^+\-><\[\].,]/', '', implode('', $block['code']));
		}

		return implode('', $result);
	}

	protected function newBlock(string $code, string $comment, bool $commentOnly = false) : void
	{
		$this->stream[] = [
			'code' => $this->split($code),
			'comment' => $comment,
			'commentOnly' => $commentOnly,
		];
	}

	protected function pushToBlock(string $code) : void
	{
		$last = count($this->stream) - 1;
		$this->stream[$last]['code'] = array_merge($this->stream[$last]['code'], $this->split($code));
	}

	protected function tryCollapseScope() : void
	{
		$scopeStartIndex = null;
		$indentCount = 0;
		foreach ($this->stream as $key => $block)
		{
			$lines = $block['code'];

			if (count($lines) > 1) { continue; }

			if ($lines[0] === ']')
			{
				$indentCount -= self::INDENT_WIDTH;
				continue;
			}
			if ($lines[0] === '[')
			{
				$indentCount += self::INDENT_WIDTH;
				$scopeStartIndex = $key;
			}
		}

		if ($scopeStartIndex === null) { throw new \Exception('something odd'); }

		$comment = $this->stream[$scopeStartIndex]['comment'];
		$scopeBlocks = array_slice($this->stream, $scopeStartIndex);
		$lines = array_merge(...array_column($scopeBlocks, 'code'));
		$combined = $this->combineLines($lines, str_repeat(' ', $indentCount));

		if (count($combined) > 1) { return; }

		$this->stream = array_slice($this->stream, 0, $scopeStartIndex);
		$this->newBlock($combined[0], $comment);
	}

	protected function split(string $code) : array
	{
		$lines = explode("\n", $code);
		return array_filter(array_map('trim', $lines));
	}

	protected function combineLines(array $lines, string $indent) : array
	{
		$result = [];
		$indentLength = mb_strlen($indent);

		$currentLine = [ $indent ];
		$currentLength = $indentLength;

		foreach ($lines as $line)
		{
			$lineLength = mb_strlen($line);

			if ($currentLength + $lineLength <= self::CODE_WIDTH)
			{
				$currentLength += $lineLength;
				$currentLine[] = $line;
			}
			else
			{
				$result[] = implode('', $currentLine);
				$currentLength = $indentLength + $lineLength;
				$currentLine = [ $indent, $line ];
			}
		}

		$result[] = implode('', $currentLine);
		return $result;
	}
}
