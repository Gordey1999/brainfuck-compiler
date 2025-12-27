<?php

namespace Gordy\Brainfuck\BigBrain;

class Processor
{
	public const string NUMBER = 'number';
	public const string BOOLEAN = 'boolean';

	protected int $pointer = 0;
	protected array $registry;

	protected OutputStream $stream;

	public function __construct(OutputStream $stream, int $registrySize)
	{
		$this->stream = $stream;
		$this->registry = array_fill(0, $registrySize, false);
	}

	public function reserve(...$near) : int
	{
		$nearest = null;
		$minDistance = 1000;
		foreach ($this->registry as $address => $value)
		{
			if ($value) { continue; }

			$distance = 0;
			foreach ($near as $nearAddress)
			{
				$distance += abs($address - $nearAddress);
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

		return $nearest;
	}

	public function reserveSeveral(int $count, ...$near) : array
	{
		$result = [];

		for ($i = 0; $i < $count; $i++)
		{
			$result[] = $this->reserve(...$near, ...$result);
		}

		return $result;
	}

	public function release(...$addresses) : void
	{
		foreach ($addresses as $address)
		{
			if (!$this->registry[$address])
			{
				throw new \RuntimeException("Addrress is already released");
			}

			$this->registry[$address] = false;
		}
	}

	public function multiply(int $a, int $b, int $result) : void
	{
		$this->while($a, function() use ($a, $b, $result) {
			$this->decrement($a);
			$this->copyNumber($b, $result);
		}, "$a * $b");
	}

	public function subUntilZero(int $from, int $sub) : void
	{
		$temp = $this->reserve($from);

		$this->while($sub, function() use ($from, $sub, $temp) {
			$this->decrement($sub);
			$this->copyNumber($from, $temp);
			$this->if($temp, function() use ($from, $sub, $temp) {
				$this->decrement($from);
			}, "if $temp not empty, decrement $from");
		}, "sub $sub from $from with zero check");

		$this->release($temp);
	}

	public function divide(int $a, int $b, int $quotient, int $remainder) : void
	{
		$temp = $this->reserve($a, $b, $quotient, $remainder);

		$this->while($a, function() use ($a, $b, $quotient, $remainder, $temp) { // проверяем, что $a не ноль
			$this->while($a, function() use ($a, $b, $quotient, $remainder, $temp) {
				$this->unset($remainder);
				$this->copyNumber($a, $remainder);
				$this->copyNumber($b, $temp);
				$this->subUntilZero($a, $temp);
				$this->increment($quotient);
			}, "division cycle");

			$this->copyNumber($remainder, $temp);
			$this->sub($temp, $b);
			$this->moveBoolean($temp, $a, $b);
			$this->if($a, function () use ($quotient) {
				$this->decrement($quotient);
			}, "if remainder > `0`, sub 1 from quotient");
			$this->not($b);
			$this->if($b, function () use ($remainder) {
				$this->unset($remainder);
			}, "else if remainder = divider, unset remainder");
		}, "divide $a by $b");
		$this->unset($b); // если $a ноль, то нужно обнулить $b

		$this->release($temp);
	}

	public function divideByConstant(int $a, int $constant, int $quotient, int $remainder) : void
	{
		[ $temp, $temp2 ] = $this->reserveSeveral(2, $a, $quotient, $remainder);

		$this->while($a, function() use ($a, $constant, $quotient, $remainder, $temp, $temp2) { // проверяем, что $a не ноль
			$this->while($a, function() use ($a, $constant, $quotient, $remainder, $temp) {
				$this->unset($remainder);
				$this->copyNumber($a, $remainder);
				$this->addConstant($temp, $constant);
				$this->subUntilZero($a, $temp);
				$this->increment($quotient);
			}, "division cycle");

			$this->copyNumber($remainder, $a);
			$this->subConstant($a, $constant);
			$this->moveBoolean($a, $temp, $temp2);
			$this->if($temp, function () use ($quotient) {
				$this->decrement($quotient);
			}, "if remainder > `0`, sub 1 from quotient");
			$this->not($temp2);
			$this->if($temp2, function () use ($remainder) {
				$this->unset($remainder);
			}, "else if remainder = `$constant`, unset remainder");
		}, "divide $a by `$constant`");

		$this->release($temp, $temp2);
	}

	public function printNumber(int $number) : void
	{
		[ $a, $b ] = $this->reserveSeveral(2, $number);

		$this->divideByConstant($number, 10, $a, $b); // $b - последняя цифра
		[ $c, $d ] = $this->reserveSeveral(2, $number, $a, $b);
		$this->copyNumber($a, $c);
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

	public function while(int $address, callable $callback, string $comment) : void
	{
		$this->goto($address);
		$this->stream->write("[", $comment);

		$callback();

		$this->goto($address);
		$this->stream->write("]");
	}

	public function if(int $address, callable $callback, string $comment) : void
	{
		$this->goto($address);
		$this->stream->write("[", $comment);
		$this->unset($address);

		$callback();

		$this->goto($address);
		$this->stream->write("]");
	}

	public function ifMoreThenConstant(int $a, int $constant, callable $callback) : void
	{
		$temp = $this->reserve($a);

		//$this->stream->startGroup("prepare if $a > $constant");
		$this->addConstant($temp, $constant);
		$this->subUntilZero($a, $temp);
		//$this->stream->endGroup();
		$this->if($a, $callback, "if $a > `$constant`");

		$this->release($temp);
	}

	public function copyNumber(int $from, ...$to) : void
	{
		$to = array_combine($to, array_fill(0, count($to), self::NUMBER));

		$this->copy($from, $to);
	}

	public function copyBoolean(int $from, ...$to) : void
	{
		$to = array_combine($to, array_fill(0, count($to), self::BOOLEAN));

		$this->copy($from, $to);
	}

	public function add(int $from, ...$to) : void
	{
		$this->stream->startGroup("add $from to " . implode(", ", $to));
		$this->moveNumber($from, ...$to);
		$this->stream->endGroup();
	}

	public function copy(int $from, array $to) : void
	{
		$temp = $this->reserve(...array_keys($to));

		$this->stream->startGroup("copy $from to " . implode(", ", array_keys($to)));
		$this->move($from, $to + [ $temp => self::NUMBER ]);
		$this->moveNumber($temp, $from);
		$this->stream->endGroup();

		$this->release($temp);
	}

	public function moveNumber(int $from, ...$to) : void
	{
		$to = array_combine($to, array_fill(0, count($to), self::NUMBER));

		$this->move($from, $to);
	}

	public function moveBoolean(int $from, ...$to) : void
	{
		$this->stream->startGroup("make bool from $from to " . implode(", ", $to));

		$to = array_combine($to, array_fill(0, count($to), self::BOOLEAN));
		$this->move($from, $to);

		$this->stream->endGroup();
	}

	public function move(int $from, array $to) : void
	{
		$toParts = [];
		ksort($to);
		$last = $from;
		foreach ($to as $address => $type)
		{
			$operation = $type === self::BOOLEAN ? '[-]+' : '+';
			$toParts[] = Encoder::goto($last, $address) . $operation;
			$last = $address;
		}

		$toList = array_keys($to);

		$this->goto($from);
		$this->stream->write(sprintf(
			"[-%s%s]\n",
			implode($toParts),
			Encoder::goto($toList[count($toList) - 1], $from)
		), "move value from $from to " . implode(', ', $toList));

		$this->pointer = $from;
	}

	public function goto(int $to) : void
	{
		$this->stream->write(Encoder::goto($this->pointer, $to), "goto $to");
		$this->pointer = $to;
	}

	public function unset(int $to) : void
	{
		$this->stream->startGroup("unset $to");
		$this->goto($to);
		$this->stream->write('[-]');
		$this->stream->endGroup();
	}

	public function not(int $bool) : void
	{
		$this->stream->startGroup("apply logical negation to $bool");
		$this->goto($bool);
		$this->subConstant($bool, 1);
		$this->stream->endGroup();
	}

	public function increment(int $to) : void
	{
		$this->stream->startGroup("increment $to");
		$this->addConstant($to, 1);
		$this->stream->endGroup();
	}

	public function decrement(int $to) : void
	{
		$this->stream->startGroup("decrement $to");
		$this->subConstant($to, 1);
		$this->stream->endGroup();
	}

	public function addConstant(int $to, int $value) : void
	{
		$this->stream->startGroup("add `$value` to $to");
		$this->goto($to);
		$value = $this->normalizeConstant($value);
		$this->stream->write(
			$value > 0 ? Encoder::plus($value) : Encoder::minus(-$value)
		);
		$this->stream->endGroup();
	}

	public function subConstant(int $from, int $value) : void
	{
		$this->stream->startGroup("sub $value from $from");
		$value = $value % 256;
		$this->goto($from);
		$value = $this->normalizeConstant($value);
		$this->stream->write(
			$value > 0 ? Encoder::minus($value) : Encoder::plus(-$value)
		);
		$this->stream->endGroup();
	}

	protected function normalizeConstant(int $value) : int
	{
		$value = $value % 256;
		if ($value > 128)
		{
			$value = -(256 - $value);
		}
		return $value;
	}

	public function sub(int $a, int $b) : void
	{
		$this->stream->startGroup("sub $b from $a");
		$this->goto($b);
		$this->stream->write(sprintf(
			"[-%s-%s]\n",
			Encoder::goto($b, $a),
			Encoder::goto($a, $b),
		));
		$this->stream->endGroup();
	}

	public function print(int $value) : void
	{
		$this->goto($value);
		$this->stream->write('.', "print $value");
	}
}