<?php

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED);

require_once $_SERVER['DOCUMENT_ROOT'] . '/brainfuck/lib/autoload.php';

use Gordy\Brainfuck\BigBrain;

$request = json_decode(file_get_contents('php://input'), true);

$stream = new BigBrain\OutputStream();
$processor = new BigBrain\Processor($stream);
$memory = new BigBrain\Memory(20); // todo
$env = new BigBrain\Environment($processor, $stream, $memory);

try
{
	$parser = new BigBrain\Parser();
	$program = $parser->parse($request['code']);

	$program->compile($env);

	if ($request['min'])
	{
		$result = sprintf("# title: .min.bf\n\n");
	}
	else
	{
		$result = sprintf("# title: .bf\n\n");
	}

	$result .= $stream->build();

	$result = [
		'status' => 'ok',
		'result' => $result,
		'min' => $request['min'],
	];
}
catch (BigBrain\Exception\Exception $e)
{
	$result = [
		'status' => 'error',
		'message' => $e->getMessage(),
		'position' => $e->getPosition(),
	];
}

echo json_encode($result);