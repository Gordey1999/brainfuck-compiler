<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Term\Expression;

interface Assignable
{
	public const string ASSIGN_SET = 'set';
	public const string ASSIGN_ADD = 'add';
	public const string ASSIGN_SUB = 'sub';
	public const string ASSIGN_MULTIPLY = 'multiply';
	public const string ASSIGN_DIVIDE = 'divide';
	public const string ASSIGN_MODULO = 'modulo';

	public const array ASSIGN_ARITHMETIC = [
		self::ASSIGN_ADD,
		self::ASSIGN_SUB,
		self::ASSIGN_MULTIPLY,
		self::ASSIGN_DIVIDE,
		self::ASSIGN_MODULO,
	];

	public function assign(Environment $env, Expression $value, string $modifier) : void;
}