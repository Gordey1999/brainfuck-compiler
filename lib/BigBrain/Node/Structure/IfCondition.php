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
	protected Node\Scope $thenBody;
	protected Node\Scope $elseBody;

	public function __construct(Node\Expression $condition, Node\Scope $thenBody, Node\Scope $elseBody, Token $token)
	{
		$this->condition = $condition;
		$this->thenBody = $thenBody;
		$this->elseBody = $elseBody;
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
				$this->elseBody->compile($env);
			}
			else
			{
				$env->stream()->blockComment($this);
				$this->thenBody->compile($env);
			}
		}
		else if ($exprType instanceof Type\Scalar)
		{
			$env->stream()->blockComment($this);

			if ($this->elseBody->empty())
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

			$env->processor()->if($then, function() use ($env, $then) {
				$env->processor()->release($then);
				$this->thenBody->compile($env);
			}, "if $then");

			if (!$this->elseBody->empty())
			{
				$env->stream()->blockComment('else');

				$env->processor()->if($else, function() use ($env, $else) {
					$env->processor()->release($else);
					$this->elseBody->compile($env);
				}, "if $then");
			}
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