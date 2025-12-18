<?php

namespace Gordy\Brainfuck\Compiler;

class Processor
{
	public const int REGISTRY_SIZE = 10;
	public const string NUMBER = 'number';
	public const string BOOLEAN = 'boolean';

	protected int $pointer = 0;
	protected array $registry;

	protected OutputStream $stream;

	public function __construct(OutputStream $stream)
	{
		$this->stream = $stream;
		$this->registry = array_fill(0, self::REGISTRY_SIZE, false);
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

		$this->registry[$nearest] = true;

		if ($nearest === null)
		{
			throw new \RuntimeException("Registry is full");
		}

		return $nearest;
	}

	// нужно ли их обнулять? По сути они обнуляются, если на их ячейке начинается цикл
	public function release($address) : void
	{
		if (!$this->registry[$address])
		{
			throw new \RuntimeException("Addrress is already released");
		}

		$this->registry[$address] = false;
	}

	// todo remove
	public function reserveSelected(int $address) : int
	{
		if ($this->registry[$address])
		{
			throw new \RuntimeException("Address is already reserved");
		}
		$this->registry[$address] = true;
		return $address;
	}


	public function multiply(int $a, int $b, int $result) : int
	{
		$this->while($a, function() use ($a, $b, $result) {
			$this->decrement($a);
			$this->copyNumber($b, $result);
		}, "$a * $b");

		return $result;
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
		}, "sub $sub from $from until zero");

		$this->release($temp);
	}

	public function divide(int $a, int $b, int $result, int $remainder) : void
	{
		$temp = $this->reserve($b);

		$this->while($a, function() use ($a, $b, $result, $remainder, $temp) { // проверяем, что $a не ноль
			$this->while($a, function() use ($a, $b, $result, $remainder, $temp) {
				$this->unset($remainder);
				$this->copyNumber($a, $remainder);
				$this->copyNumber($b, $temp);
				$this->subUntilZero($a, $temp);
				$this->increment($result);
			}, "sub $b from $a until zero");

			$this->copyNumber($remainder, $temp);
			$this->sub($temp, $b);
			$this->moveBoolean($temp, $a, $b);
			$this->if($a, function () use ($result, $temp) {
				$this->decrement($result);
			}, "if remainder - dividend > 0, sub 1 from result");
			$this->not($b);
			$this->if($b, function () use ($remainder) {
				$this->unset($remainder);
			}, "else if remainder - dividend = 0, unset remainder");
		}, "devide $a by $b");
		$this->unset($b);



		$this->release($temp);
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

		$callback();

		$this->unset($address);
		$this->stream->write("]");
	}

	// todo add alias, can collapse
	// todo copy to several, copy bool, sub, subUntilNull
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
		$this->copyNumber($from, $to);
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

	// todo copy($from, [$to1=>int, $to2=>bool]

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
		$this->stream->write(
			$value > 0 ? Encoder::plus($value) : Encoder::minus(-$value)
		);
		$this->stream->endGroup();
	}

	public function subConstant(int $from, int $value) : void
	{
		$this->stream->startGroup("sub $value from $from");
		$this->goto($from);
		$this->stream->write(
			$value > 0 ? Encoder::minus($value) : Encoder::plus(-$value)
		);
		$this->stream->endGroup();
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

	public function set(int $to, int $value, OutputStream $stream) : void
	{
		$this->goto($to, $stream);
		$this->unset($to, $stream);
		$this->addConstant($to, $value, $stream);
	}
}