<?php

namespace Gordy\Brainfuck\BigBrain\Term\Expression;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Term\Expression;

interface Assignable
{
	public const string ASSIGN_SET = 'set';
	public const string ASSIGN_ADD = 'add';
	public const string ASSIGN_SUB = 'sub';

	public function assign(Environment $env, Expression $value, string $modifier) : void;
}