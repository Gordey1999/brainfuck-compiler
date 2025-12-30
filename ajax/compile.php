<?php

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED);

require_once $_SERVER['DOCUMENT_ROOT'] . '/brainfuck/lib/autoload.php';

use Gordy\Brainfuck\BigBrain;

$request = json_decode(file_get_contents('php://input'), true);

$log = "compiling...\n";

try
{
	$parser = new BigBrain\Parser();
	$program = $parser->parse($request['code']);

	$stream = new BigBrain\OutputStream();
	$processor = new BigBrain\FakeProcessor($stream, 100);
	$memory = new BigBrain\Memory($stream, 100);
	$env = new BigBrain\Environment($processor, $stream, $memory);
	$program->compile($env);

	$registrySize = $processor->computedRegistrySize();
	$log .= "registry size computed: $registrySize\n";

	$stream = new BigBrain\OutputStream();
	$processor = new BigBrain\Processor($stream, $registrySize);
	$memory = new BigBrain\Memory($stream, $registrySize);
	$env = new BigBrain\Environment($processor, $stream, $memory);
	$program->compile($env);

	$min = $stream->buildMin();
	$minLength = strlen($min);

	if ($request['min'])
	{
		$result = sprintf("# title: .min.bf\n\n");
		$minLines = mb_str_split($min, 100);
		$result .= implode("\n", $minLines);
	}
	else
	{
		$result = sprintf("# title: .bf\n\n");
		$result .= $stream->build();
	}


	$log .= "finished! code length: $minLength\n";

	$result = [
		'status' => 'ok',
		'result' => $result,
		'log' => $log,
	];
}
catch (BigBrain\Exception\Exception $e)
{
	$lexeme = $e->getLexeme();

	$result = [
		'status' => 'error',
		'message' => $e->getMessage(),
		'position' => [
			'start' => $lexeme->index(),
			'length'  => mb_strlen($lexeme->value()),
		],
	];
}

echo json_encode($result);