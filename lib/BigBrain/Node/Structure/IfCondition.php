<?php

namespace Gordy\Brainfuck\BigBrain\Node\Structure;

use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Node;
use Gordy\Brainfuck\BigBrain\Type;

class IfCondition implements Node\Structure
{
	use Node\HasToken;

	protected Node\Expression $condition;
	protected Node\Scope $thenScope;
	protected Node\Scope $elseScope;

	public function __construct(Node\Expression $condition, Node\Scope $thenScope, Node\Scope $elseScope, Token $token)
	{
		$this->condition = $condition;
		$this->thenScope = $thenScope;
		$this->elseScope = $elseScope;
		$this->token = $token;
	}
	public function compile(Environment $env) : void
	{
		$exprType = $this->condition->resultType($env);

		if ($exprType instanceof Type\Computable && $exprType->numericCompatible())
		{
			if ($exprType->getNumeric() === 0)
			{
				$env->stream()->blockComment('else');
				$this->elseScope->compile($env);
			}
			else
			{
				$env->stream()->blockComment($this);
				$this->thenScope->compile($env);
			}
		}
		else if ($exprType instanceof Type\Scalar)
		{
			$env->stream()->blockComment($this);

			if ($this->elseScope->empty())
			{
				$then = $env->processor()->reserve();
				$this->condition->compileCalculation($env, $then);
			}
			else
			{
				$temp = $env->processor()->reserve();
				$this->condition->compileCalculation($env, $temp);
				[ $then, $else ] = $env->processor()->reserveSeveral(2, $temp);
				$env->processor()->moveBoolean($temp, $then, $else);
				$env->processor()->not($else);
				$env->processor()->release($temp);
			}

			$env->processor()->if($then, function() use ($env) {
				$this->thenScope->compile($env);
			}, "if $then");

			if (!$this->elseScope->empty())
			{
				$env->stream()->blockComment('else');

				$env->processor()->if($else, function() use ($env) {
					$this->elseScope->compile($env);
				}, "if $then");

				$env->processor()->release($else);
			}

			$env->processor()->release($then);
		}
		else
		{
			throw new CompileError('scalar condition expected', $this->condition->token());
		}
	}

	public function __toString() : string
	{
		$expr = $this->condition;
		return "if ($expr)";
	}
}