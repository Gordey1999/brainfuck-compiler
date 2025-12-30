<?php

namespace Gordy\Brainfuck\BigBrain\Term\Command;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Utils;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class Output implements Term\Command
{
	use Term\HasLexeme;

	/** @var Expression[] */
	private array $parts;

	public function __construct(Expression $expr, Lexeme $lexeme)
	{
		$this->parts = $this->getParts($expr);
		$this->lexeme = $lexeme;
	}

	protected function getParts(Expression $expr) : array
	{
		if ($expr instanceof Expression\Operator\Comma)
		{
			return $expr->list();
		}
		else
		{
			return [ $expr ];
		}
	}

	public function compile(BigBrain\Environment $env) : void
	{
		foreach ($this->parts as $part)
		{
			$env->stream()->blockComment("out $part");

			$resultType = $part->resultType($env);

			if ($resultType instanceof Type\Computable)
			{
				$this->printComputable($env, $resultType);
			}
			else if ($part instanceof Expression\Variable && $resultType instanceof Type\Char)
			{
				$env->processor()->print($part->memoryCell($env));
			}
			else
			{
				$this->printExpression($env, $part, $resultType);
			}
		}
	}

	public function printComputable(Environment $env, Type\Computable $result) : void
	{
		$temp = $env->processor()->reserve();
		$bytes = Utils\CharHelper::stringToBytes($result->getString());

		$last = 0;
		foreach ($bytes as $byte)
		{
			$add = $byte - $last;
			$env->processor()->addConstant($temp, $add);
			$env->processor()->print($temp);
			$last = $byte;
		}

		$env->processor()->unset($temp);
		$env->processor()->release($temp);
	}

	public function printExpression(Environment $env, Expression $expr, Type\Type $resultType) : void
	{
		$result = $env->processor()->reserve();

		$expr->compileCalculation($env, $result);

		if ($resultType instanceof Type\Char)
		{
			$env->processor()->print($result);
		}
		else if ($resultType instanceof Type\Boolean)
		{
			$env->processor()->addConstant($result, 48);
			$env->processor()->print($result);
			$env->processor()->unset($result);
		}
		else
		{
			$env->processor()->printNumber($result);
		}

		$env->processor()->release($result);
	}

	public function __toString() : string
	{
		return 'out ' . implode(', ', $this->parts);
	}
}