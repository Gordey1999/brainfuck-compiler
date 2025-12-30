<?php

namespace Gordy\Brainfuck\BigBrain\Builder;

use Gordy\Brainfuck\BigBrain\Type;
use Gordy\Brainfuck\BigBrain\Parser\LexemeScope;
use Gordy\Brainfuck\BigBrain\Term;

class CommandBuilder
{
	public const string TYPE_BYTE = 'byte';
	public const string TYPE_CHAR = 'char';
	public const string TYPE_BOOL = 'bool';
	public const string IN = 'in';
	public const string OUT = 'out';

	public const array VAR_TYPES = [
		self::TYPE_BYTE,
		self::TYPE_CHAR,
		self::TYPE_BOOL,
	];

	public static function build(LexemeScope $scope) : Term\Command | Term\Expression
	{
		$first = $scope->first();

		if (in_array($first->value(), self::VAR_TYPES))
		{
			return self::buildVariable($scope);
		}
		else if ($first->value() === self::OUT)
		{
			$expr = ExpressionBuilder::build($scope->slice(1));
			return new Term\Command\Output($expr, $first);
		}
		else
		{
			return ExpressionBuilder::build($scope);
		}
	}

	public static function buildVariable(LexemeScope $scope) : Term\Command
	{
		$type = $scope->first();
		$expr = $scope->slice(1);

		$typeObj = match ($type->value()) {
			self::TYPE_BYTE => new Type\Byte(),
			self::TYPE_CHAR => new Type\Char(),
			self::TYPE_BOOL => new Type\Boolean(),
		};
		return new Term\Command\DefineVariable($typeObj, ExpressionBuilder::build($expr), $type);
	}
}