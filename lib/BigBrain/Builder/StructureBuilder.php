<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

use Gordy\Brainfuck\BigBrain\Exception\SyntaxError;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Parser\TokenScope;

class StructureBuilder
{
	public const string WHILE = 'while';

	protected Names $names;
	protected ExpressionBuilder $expression;
	protected CommandBuilder $command;

	public function __construct(Names $names)
	{
		$this->names = $names;
		$this->expression = new ExpressionBuilder($names);
		$this->command = new CommandBuilder($names);
	}

	public function buildScope(TokenScope $scope) : Term\Scope
	{
		$result = [];
		// $this->rebuildIf()

		foreach ($scope->children() as $block)
		{
			if (!$block instanceof TokenScope || !$block->isBlock())
			{
				throw new \Exception('unexpected error');
			}
			if ($block->count() === 0) { continue; }

			$result[] = $this->buildBlock($block);
		}

		return new Term\Scope($result, $scope);
	}

	public function buildBlock(TokenScope $block) : Term\Term
	{
		$first = $block->first();
		$other = $block->slice(1);

		if ($first->value() === self::WHILE)
		{
			return $this->buildWhile($other);
		}
		else
		{
			return $this->command->build($block);
		}
	}

	protected function buildWhile(TokenScope $block) : Term\Structure\WhileLoop
	{
		if ($block->count() < 2) { throw new SyntaxError("unexpected structure", $block); }
		$expr = $block->first();
		$scope = $block->slice(1);
		if (!$expr instanceof TokenScope || $expr->value() !== '(')
		{
			throw new SyntaxError("structure scopes expected", $expr);
		}
		if ($scope->count() === 1 && $scope->first()->value() === '{')
		{
			$scope = $scope->first();
		}
		else
		{
			$scope = new TokenScope('{', [$scope], $scope->first()->index(), $scope->first()->position());
		}

		return new Term\Structure\WhileLoop(
			$this->expression->build($expr),
			$this->buildScope($scope),
			$scope
		);
	}
}