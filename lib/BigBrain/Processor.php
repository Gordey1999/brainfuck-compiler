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

		foreach ($this->registry as $address => $isReserved)
		{
			$this->stream->memoryComment($address, "R$address");
		}
	}

	public function reserve(MemoryCell ...$near) : MemoryCell
	{
		$nearest = null;
		$minDistance = 1000;
		foreach ($this->registry as $address => $isReserved)
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
			$this->copyNumber($b, $result);
		}, "$a * $b");
	}

	public function subUntilZero(MemoryCell $from, MemoryCell $sub) : void
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

	public function divide(MemoryCell $a, MemoryCell $b, MemoryCell $quotient, MemoryCell $remainder) : void
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

	public function divideByConstant(MemoryCell $a, int $constant, MemoryCell $quotient, MemoryCell $remainder) : void
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

	public function printNumber(MemoryCell $number) : void
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

	public function copyNumber(MemoryCell $from, MemoryCell ...$to) : void
	{
		$to = array_map(static function ($cell) {
			return [ $cell, self::NUMBER ];
		}, $to);

		$this->copy($from, $to);
	}

	public function copyBoolean(MemoryCell $from, MemoryCell ...$to) : void
	{
		$to = array_map(static function ($cell) {
			return [ $cell, self::BOOLEAN ];
		}, $to);

		$this->copy($from, $to);
	}

	public function add(MemoryCell $from, MemoryCell ...$to) : void
	{
		$this->stream->startGroup("add $from to " . implode(", ", $to));
		$this->moveNumber($from, ...$to);
		$this->stream->endGroup();
	}

	/** @param array<int, array{0: MemoryCell, 1: string}> $to */
	public function copy(MemoryCell $from, array $to) : void
	{
		$cells = array_column($to, 0);
		$temp = $this->reserve(...$cells);

		$this->stream->startGroup("copy $from to " . implode(", ", $cells));
		$to[] = [ $temp, self::NUMBER ];
		$this->move($from, $to);
		$this->moveNumber($temp, $from);
		$this->stream->endGroup();

		$this->release($temp);
	}

	public function moveNumber(MemoryCell $from, MemoryCell ...$to) : void
	{
		$to = array_map(static function ($cell) {
			return [ $cell, self::NUMBER ];
		}, $to);

		$this->move($from, $to);
	}

	public function moveBoolean(MemoryCell $from, MemoryCell ...$to) : void
	{
		$this->stream->startGroup("make bool from $from to " . implode(", ", $to));

		$to = array_map(static function ($cell) {
			return [ $cell, self::BOOLEAN ];
		}, $to);

		$this->move($from, $to);

		$this->stream->endGroup();
	}

	/** @param array<int, array{0: MemoryCell, 1: string}> $to */
	public function move(MemoryCell $from, array $to) : void
	{
		$toParts = [];
		usort($to, function($a, $b) {
			return $a[0]->address() <=> $b[0]->address();
		});

		$last = $from;
		/** @var MemoryCell $cell */
		/** @var string $type */
		foreach ($to as [$cell, $type])
		{
			$operation = $type === self::BOOLEAN ? '[-]+' : '+';
			$toParts[] = Encoder::goto($last->address(), $cell->address()) . $operation;
			$last = $cell;
		}

		$toCells = array_column($to, 0);

		$this->goto($from);
		$this->stream->write(sprintf(
			"[-%s%s]\n",
			implode($toParts),
			Encoder::goto($last->address(), $from->address())
		), "move value from $from to " . implode(', ', $toCells));

		$this->pointer = $from->address();
	}

	public function goto(MemoryCell $to) : void
	{
		$this->stream->write(Encoder::goto($this->pointer, $to->address()), "goto $to");
		$this->pointer = $to->address();
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
		$this->stream->startGroup("apply logical negation to $bool");
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
		$this->stream->startGroup("add `$value` to $to");
		$this->goto($to);
		$value = $this->normalizeConstant($value);
		$this->stream->write(
			$value > 0 ? Encoder::plus($value) : Encoder::minus(-$value)
		);
		$this->stream->endGroup();
	}

	public function subConstant(MemoryCell $from, int $value) : void
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
		$this->goto($value);
		$this->stream->write('.', "print $value");
	}
}