<?php

namespace Gordy\Brainfuck\BigBrain\Term\Command;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Exception\CompileError;
use Gordy\Brainfuck\BigBrain\Term\Expression\Operator\ArrayAccess;
use Gordy\Brainfuck\BigBrain\Utils;
use Gordy\Brainfuck\BigBrain\Parser\Token;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class Input implements Term\Command
{
	use Term\HasToken;

	/** @var Expression[] */
	private array $parts;

	public function __construct(Expression $expr, Token $token)
	{
		$this->parts = $this->getParts($expr);
		$this->token = $token;
	}

	/** @return Expression[] */
	protected function getParts(Expression $expr) : array
	{
		if ($expr instanceof Expression\Operator\Comma)
		{
			$list = $expr->list();
		}
		else
		{
			$list = [ $expr ];
		}

		return $list;
	}

	public function compile(BigBrain\Environment $env) : void
	{
		foreach ($this->parts as $part)
		{
			$env->stream()->blockComment("in $part");

			$resultType = $part->resultType($env);

			if ($resultType instanceof Type\Computable)
			{
				$this->inputDummy($env);
			}
			else if ($part instanceof ArrayAccess && $resultType instanceof Type\Scalar)
			{
				$this->inputArrayIndex($env, $part);
			}
			else if ($part instanceof Expression\ScalarVariable)
			{
				$this->inputVariable($env, $part);
			}
			else if ($resultType instanceof Type\Pointer && $resultType->valueType() instanceof Type\Char)
			{
				$this->inputString($env, $part);
			}
			else
			{
				throw new CompileError("command in: type '$resultType' not supported", $part->token());
			}
		}
	}

	public function inputDummy(Environment $env) : void
	{
		$temp = $env->processor()->reserve();

		$env->processor()->input($temp);
		$env->processor()->unset($temp);

		$env->processor()->release($temp);
	}

	public function inputVariable(Environment $env, Expression\ScalarVariable $var) : void
	{
		$cell = $var->memoryCell($env);

		if ($cell->type() instanceof Type\Char)
		{
			$env->processor()->input($cell);
		}
		else if ($cell->type() instanceof Type\Boolean)
		{
			$temp = $env->processor()->reserve($cell);

			$env->processor()->input($temp);
			$env->processor()->unset($cell);
			$env->processor()->notEqualsToConstant($temp, Utils\CharHelper::charToNumber('0'), $cell);

			$env->processor()->release($temp);
		}
		else if ($cell->type() instanceof Type\Byte)
		{
			$this->inputNumber($env, $var);
		}
	}

	protected function inputArrayIndex(Environment $env, ArrayAccess $expr) : void
	{
		$cell = $env->arraysProcessor()->startCell();
		$expr->calculateIndex($env, $cell);
		$env->arraysProcessor()->input($cell);
	}

	public function inputNumber(Environment $env, Expression\ScalarVariable $var) : void
	{
		$result = $var->memoryCell($env);
		$proc = $env->processor();

		[$in, $a, $b, $c] = $proc->reserveSeveral(4, $result);
		$proc->input($in);
		$proc->subConstant($in, 48);
		$proc->add($in, $result);
		$proc->input($in);
		$proc->while($in, static function () use ($proc, $in, $result, $a, $b, $c) {
			$proc->copy($in, $a, $b);
			$proc->notEqualsToConstant($a, Utils\CharHelper::charToNumber(" "), $c); // не пробел
			$proc->notEqualsToConstant($b, Utils\CharHelper::charToNumber("\n"), $c); // не интер
			$proc->equalsToConstant($c, 2, $b); // пробел или интер не нажаты
			$proc->move($in, $a); // переносим инпут, чтобы прекратить цикл
			$proc->if($b, static function () use ($proc, $in, $result, $a, $b) {
				$proc->move($result, $b);
				$proc->multiplyByConstant($b, 10, $result);
				$proc->subConstant($a, 48);
				$proc->add($a, $result);
				$proc->input($in);
			}, 'if not enter or constant');
			$proc->unset($a);
		}, 'input number cycle');

		$proc->release($in, $a, $b, $c);
	}

	protected function inputString(Environment $env, Expression $part) : void
	{
		if ($part instanceof Expression\ArrayVariable)
		{
			$startCell = $part->memoryCell($env);
			$env->arraysProcessor()->inputString($startCell);
		}
		else if ($part instanceof ArrayAccess)
		{
			$indexCell = $env->arraysProcessor()->startCell();
			$part->calculateIndex($env, $indexCell);
			$env->arraysProcessor()->inputString($indexCell);
		}
		else
		{
			throw new CompileError('something went wrong', $part->token());
		}
	}

	public function __toString() : string
	{
		return 'in ' . implode(', ', $this->parts);
	}
}