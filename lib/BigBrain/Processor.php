<?php

namespace Gordy\Brainfuck\BigBrain;
use Gordy\Brainfuck\BigBrain\Utils\Encoder;

class Processor
{
	protected int $pointer = 0;
	protected array $registry;
	protected bool $uglify;

	protected OutputStream $stream;

	public function __construct(OutputStream $stream, int $registrySize, bool $uglify)
	{
		$this->stream = $stream;
		$this->registry = array_fill(0, $registrySize, false);
		$this->uglify = $uglify;

		foreach ($this->registry as $address => $isReserved)
		{
			$this->stream->memoryComment($address, "R$address");
		}
	}

	public function reserve(MemoryCell ...$near) : MemoryCell
	{
		if (empty($near))
		{
			$near = [
				new MemoryCell($this->pointer, 'dummy'),
			];
		}

		$nearest = null;
		$minDistance = 100000;
		foreach (array_reverse($this->registry, true) as $address => $isReserved)
		{
			if ($isReserved) { continue; }

			$distance = 0;
			foreach ($near as $nearAddress)
			{
				$distance += abs($address - $nearAddress->address());
			}

			if ($distance < $minDistance)
			{
				$nearest = $address;
				$minDistance = $distance;
			}
		}

		if ($nearest === null)
		{
			throw new \RuntimeException("Registry is full");
		}

		$this->registry[$nearest] = true;

		return new MemoryCell($nearest, "R$nearest");
	}

	/** @internal */
	public function setPointer(MemoryCell $pointer) : void
	{
		$this->pointer = $pointer->address();
	}

	public function reserveSeveral(int $count, MemoryCell ...$near) : array
	{
		$result = [];

		for ($i = 0; $i < $count; $i++)
		{
			$result[] = $this->reserve(...$near, ...$result);
		}

		return $result;
	}

	public function release(MemoryCell ...$cells) : void
	{
		foreach ($cells as $cell)
		{
			if (!$this->registry[$cell->address()])
			{
				throw new \RuntimeException("Addrress is already released");
			}

			$this->registry[$cell->address()] = false;
		}
	}

	public function multiply(MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$this->while($a, function() use ($a, $b, $result) {
			$this->decrement($a);
			$this->copy($b, $result);
		}, "$result = $a * $b");
		$this->unset($b);
	}

	public function multiplyByConstant(MemoryCell $a, int $constant, MemoryCell $result) : void
	{
		$this->while($a, function() use ($a, $constant, $result) {
			$this->decrement($a);
			$this->addConstant($result, $constant);
		}, "$result = $a * `$constant`");
	}

	public function subUntilZero(MemoryCell $from, MemoryCell $sub) : void
	{
		$temp = $this->reserve($from);

		$this->while($sub, function() use ($from, $sub, $temp) {
			$this->decrement($sub);
			$this->copy($from, $temp);
			$this->if($temp, function() use ($from, $sub, $temp) {
				$this->decrement($from);
			}, "if $temp not empty, decrement $from");
		}, "sub $sub from $from with zero check");

		$this->release($temp);
	}

	public function divide(MemoryCell $a, MemoryCell $b, MemoryCell $quotient, MemoryCell $remainder) : void
	{
		$temp = $this->reserve($a, $b, $quotient, $remainder);

		$this->while($a, function() use ($a, $b, $quotient, $remainder, $temp) { // проверяем, что $a не ноль
			$this->while($a, function() use ($a, $b, $quotient, $remainder, $temp) {
				$this->unset($remainder);
				$this->copy($a, $remainder);
				$this->copy($b, $temp);
				$this->subUntilZero($a, $temp);
				$this->increment($quotient);
			}, "division cycle");

			$this->copy($remainder, $temp);
			$this->sub($temp, $b);
			$this->moveBoolean($temp, $a, $b);
			$this->if($a, function () use ($quotient) {
				$this->decrement($quotient);
			}, "if remainder > `0`, sub `1` from quotient");
			$this->not($b);
			$this->if($b, function () use ($remainder) {
				$this->unset($remainder);
			}, "else if remainder = divider, unset remainder");
		}, "divide $a by $b");
		$this->unset($b); // если $a ноль, то нужно обнулить $b

		$this->release($temp);
	}

	public function divideByConstant(MemoryCell $a, int $constant, MemoryCell $quotient, MemoryCell $remainder) : void
	{
		$temp = $this->reserve($a, $quotient, $remainder);

		$this->while($a, function() use ($a, $constant, $quotient, $remainder, $temp) { // проверяем, что $a не ноль
			$this->while($a, function() use ($a, $constant, $quotient, $remainder, $temp) {
				$this->unset($remainder);
				$this->copy($a, $remainder);
				$this->addConstant($temp, $constant);
				$this->subUntilZero($a, $temp);
				$this->increment($quotient);
			}, "division cycle");

			$this->copy($remainder, $a);
			$this->subConstant($a, $constant);
			$temp2 = $this->reserve($a, $quotient, $remainder, $temp);
			$this->moveBoolean($a, $temp, $temp2);
			$this->if($temp, function () use ($quotient) {
				$this->decrement($quotient);
			}, "if remainder > `0`, sub `1` from quotient");
			$this->not($temp2);
			$this->if($temp2, function () use ($remainder) {
				$this->unset($remainder);
			}, "else if remainder = `$constant`, unset remainder");
			$this->release($temp2);
		}, "divide $a by `$constant`");

		$this->release($temp);
	}

	public function printNumber(MemoryCell $number) : void
	{
		[ $a, $b ] = $this->reserveSeveral(2, $number);

		$this->divideByConstant($number, 10, $a, $b); // $b - последняя цифра
		[ $c, $d ] = $this->reserveSeveral(2, $number, $a, $b);
		$this->copy($a, $c);
		$this->ifMoreThenConstant($c, 9, function() use ($a, $c, $d) {
			$this->divideByConstant($a, 10, $c, $d); // $c - 1 цифра, $d - вторая
			$this->addConstant($c, 48);
			$this->print($c);
			$this->unset($c);
			$this->addConstant($d, 48);
			$this->print($d);
			$this->unset($d);
		});
		$this->while($a, function() use ($a) {
			$this->addConstant($a, 48);
			$this->print($a);
			$this->unset($a);
		}, "if 2 digit number");
		$this->addConstant($b, 48);
		$this->print($b);
		$this->unset($b);

		$this->release($a, $b, $c, $d);
	}

	public function while(MemoryCell $value, callable $callback, string $comment) : void
	{
		$this->goto($value);
		$this->stream->write("[", $comment);

		$callback();

		$this->goto($value);
		$this->stream->write("]");
	}

	public function if(MemoryCell $value, callable $callback, string $comment) : void
	{
		$this->goto($value);
		$this->stream->write("[", $comment);
		$this->unset($value);

		$callback();

		$this->goto($value);
		$this->stream->write("]");
	}

	public function equals(MemoryCell $a, MemoryCell $b, MemoryCell $result) : void
	{
		$this->stream->startGroup("$result = $a === $b");
		$this->sub($a, $b);
		$this->moveBoolean($a, $result);
		$this->not($result);
		$this->stream->endGroup();
	}

	public function equalsToConstant(MemoryCell $value, int $constant, MemoryCell $result) : void
	{
		$this->stream->startGroup("$result = $value === `$constant`");
		$this->subConstant($value, $constant);
		$this->moveBoolean($value, $result);
		$this->not($result);
		$this->stream->endGroup();
	}

	public function notEqualsToConstant(MemoryCell $value, int $constant, MemoryCell $result) : void
	{
		$this->stream->startGroup("$result = $value !== `$constant`");
		$this->subConstant($value, $constant);
		$this->moveBoolean($value, $result);
		$this->stream->endGroup();
	}

	public function ifMoreThenConstant(MemoryCell $a, int $constant, callable $callback) : void
	{
		$temp = $this->reserve($a);

		//$this->stream->startGroup("prepare if $a > $constant");
		$this->addConstant($temp, $constant);
		$this->subUntilZero($a, $temp);
		//$this->stream->endGroup();
		$this->if($a, $callback, "if $a > `$constant`");

		$this->release($temp);
	}

	public function add(MemoryCell $from, MemoryCell ...$to) : void
	{
		$this->stream->startGroup("add $from to " . implode(", ", $to));
		$this->move($from, ...$to);
		$this->stream->endGroup();
	}

	public function copy(MemoryCell $from, MemoryCell ...$to) : void
	{
		if (empty($to)) { return; }

		$temp = $this->reserve(...$to);

		$this->stream->startGroup("copy $from to " . implode(", ", $to));
		$to[] = $temp;
		$this->move($from, ...$to);
		$this->move($temp, $from);
		$this->stream->endGroup();

		$this->release($temp);
	}

	public function move(MemoryCell $from, MemoryCell ...$to) : void
	{
		if (empty($to)) { return; }

		$toParts = [];
		usort($to, function($a, $b) {
			return $a->address() <=> $b->address();
		});

		$last = $from;
		foreach ($to as $cell)
		{
			$toParts[] = Encoder::goto($last->address(), $cell->address()) . '+';
			$last = $cell;
		}

		$this->stream->startGroup("move from $from to " . implode(', ', $to));
		$this->goto($from);
		$this->stream->write(sprintf(
			"[-%s%s]\n",
			implode($toParts),
			Encoder::goto($last->address(), $from->address())
		));
		$this->stream->endGroup();

		$this->setPointer($from);
	}

	public function moveBoolean(MemoryCell $from, MemoryCell ...$to) : void
	{
		if (empty($to)) { return; }

		$toParts = [];
		usort($to, function($a, $b) {
			return $a->address() <=> $b->address();
		});

		$last = $from;
		foreach ($to as $cell)
		{
			$toParts[] = Encoder::goto($last->address(), $cell->address()) . '+';
			$last = $cell;
		}

		$this->stream->startGroup("make bool from $from to " . implode(", ", $to));
		$this->goto($from);
		$this->stream->write(sprintf(
			"[[-]%s%s]\n",
			implode($toParts),
			Encoder::goto($last->address(), $from->address())
		));
		$this->stream->endGroup();

		$this->setPointer($from);
	}

	public function goto(MemoryCell $to) : void
	{
		$this->stream->write(Encoder::goto($this->pointer, $to->address()), "goto $to");
		$this->setPointer($to);
	}

	public function unsetSeveral(MemoryCell ...$to) : void
	{
		sort($to);
		foreach ($to as $address)
		{
			$this->unset($address);
		}
	}

	public function unset(MemoryCell $to) : void
	{
		$this->stream->startGroup("unset $to");
		$this->goto($to);
		$this->stream->write('[-]');
		$this->stream->endGroup();
	}

	public function not(MemoryCell $bool) : void
	{
		$this->stream->startGroup("$bool = !$bool");
		$this->goto($bool);
		$this->subConstant($bool, 1);
		$this->stream->endGroup();
	}

	public function increment(MemoryCell $to) : void
	{
		$this->stream->startGroup("increment $to");
		$this->addConstant($to, 1);
		$this->stream->endGroup();
	}

	public function decrement(MemoryCell $to) : void
	{
		$this->stream->startGroup("decrement $to");
		$this->subConstant($to, 1);
		$this->stream->endGroup();
	}

	public function addConstant(MemoryCell $to, int $value) : void
	{
		if ($value > 0)
		{
			$this->stream->startGroup("add `$value` to $to");
		}
		else
		{
			$printValue = -$value;
			$this->stream->startGroup("sub `$printValue` from $to");
		}

		$value = Utils\ModuloHelper::normalizeConstant($value);

		if ($this->uglify && abs($value) > 14)
		{
			[$a, $b, $c] = Utils\NumbersHelper::factorize(abs($value));
			$temp = $this->reserve($to);
			$this->addConstantSimple($temp, $a);
			$this->multiplyByConstantSimple($temp, $value > 0 ? $b : -$b, $to);
			if ($c !== 0)
			{
				$this->addConstantSimple($to, $value > 0 ? $c : -$c);
			}
			$this->release($temp);
		}
		else
		{
			$this->addConstantSimple($to, $value);
		}

		$this->stream->endGroup();
	}

	protected function addConstantSimple(MemoryCell $to, int $value) : void
	{
		$this->goto($to);
		$this->stream->write(
			$value > 0 ? Encoder::plus($value) : Encoder::minus(-$value)
		);
	}

	public function multiplyByConstantSimple(MemoryCell $a, int $constant, MemoryCell $result) : void
	{
		$this->goto($a);
		$this->stream->write(sprintf('[-%s%s%s]',
			Encoder::goto($a->address(), $result->address()),
			$constant > 0 ? Encoder::plus($constant) : Encoder::minus(-$constant),
			Encoder::goto($result->address(), $a->address()),
		));
	}

	public function subConstant(MemoryCell $from, int $value) : void
	{
		$this->addConstant($from, -$value);
	}

	public function sub(MemoryCell $a, MemoryCell $b) : void
	{
		$this->stream->startGroup("sub $b from $a");
		$this->goto($b);
		$this->stream->write(sprintf(
			"[-%s-%s]\n",
			Encoder::goto($b->address(), $a->address()),
			Encoder::goto($a->address(), $b->address()),
		));
		$this->stream->endGroup();
	}

	public function print(MemoryCell $value) : void
	{
		$this->stream->startGroup("print $value");
		$this->goto($value);
		$this->stream->write('.');
		$this->stream->endGroup();
	}

	public function input(MemoryCell $to) : void
	{
		$this->stream->startGroup("input $to");
		$this->goto($to);
		$this->stream->write(',');
		$this->stream->endGroup();
	}
}