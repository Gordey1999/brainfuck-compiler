<?php

namespace Gordy\Brainfuck\BigBrain\Term\Command;

use Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Environment;
use Gordy\Brainfuck\BigBrain\Utils;
use Gordy\Brainfuck\BigBrain\Parser\Lexeme;
use Gordy\Brainfuck\BigBrain\Term;
use Gordy\Brainfuck\BigBrain\Term\Expression;
use Gordy\Brainfuck\BigBrain\Type;

class Input implements Term\Command
{
	use Term\HasLexeme;

	/** @var Expression */
	private array $parts;

	public function __construct(Expression $expr, Lexeme $lexeme)
	{
		$this->parts = $this->getParts($expr);
		$this->lexeme = $lexeme;
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

			if (!$part instanceof Expression\Variable)
			{
				$this->inputDummy($env);
			}
			else
			{
				$this->inputVariable($env, $part);
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

	public function inputVariable(Environment $env, Expression\Variable $var) : void
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
			$env->processor()->notEqualsToConstant($temp, Utils\CharHelper::charToNumber('0'), $cell);

			$env->processor()->release($temp);
		}
		else if ($cell->type() instanceof Type\Byte)
		{
			$this->inputNumber($env, $var);
		}
	}

	public function inputNumber(Environment $env, Expression\Variable $var) : void
	{
		$result = $var->memoryCell($env);
		$proc = $env->processor();

		[$in, $a, $b, $c] = $proc->reserveSeveral(4, $result);
		$proc->input($in);
		$proc->subConstant($in, 48);
		$proc->add($in, $result);
		$proc->input($in);
		$proc->while($in, static function () use ($proc, $in, $result, $a, $b, $c) {
			$proc->copyNumber($in, $a);
			$proc->notEqualsToConstant($a, Utils\CharHelper::charToNumber(" "), $b); // не пробел
			$proc->copyNumber($in, $a);
			$proc->notEqualsToConstant($a, Utils\CharHelper::charToNumber("\n"), $c); // не интер
			$proc->add($b, $c); // если 2, то ни то, ни другое
			$proc->equalsToConstant($c, 2, $b); // пробел или интер не нажаты
			$proc->moveNumber($in, $a); // переносим инпут, чтобы прекратить цикл
			$proc->if($b, static function () use ($proc, $in, $result, $a, $b) {
				$proc->moveNumber($result, $b);
				$proc->multiplyByConstant($b, 10, $result);
				$proc->subConstant($a, 48);
				$proc->add($a, $result);
				$proc->input($in);
			}, 'if not enter or constant');
			$proc->unset($a);
		}, 'input number cycle');

		$proc->release($in, $a, $b, $c);
	}

	public function __toString() : string
	{
		return 'in ' . implode(', ', $this->parts);
	}
}