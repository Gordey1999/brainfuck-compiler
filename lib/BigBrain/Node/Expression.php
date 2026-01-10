<?php

namespace Gordy\Brainfuck\BigBrain\Node;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\MemoryCell;
use Gordy\Brainfuck\BigBrain\Type;

interface Expression extends Node
{
	public function resultType(Environment $env) : Type\Type;

	public function compileCalculation(Environment $env, MemoryCell $result) : void;

	public function hasVariable(string $name) : bool; // todo remove
}