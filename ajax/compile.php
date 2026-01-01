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

	$precompileEnv = BigBrain\Environment::makeForPrecompile(100, 500, 256);
	$program->compile($precompileEnv);

	$registrySize = $precompileEnv->processor()->computedRegistrySize();
	$memorySize = $precompileEnv->memory()->computedMemorySize();
	$arraysMemorySize = $precompileEnv->arraysMemory()->computedMemorySize();

	$log .= "registry size computed: $registrySize\n";
	$log .= "memory size computed: $memorySize\n";
	$log .= "arrays memory size computed: $arraysMemorySize\n";

	$env = BigBrain\Environment::makeForRelease($registrySize, $memorySize, $arraysMemorySize);
	$program->compile($env);

	$min = $env->stream()->buildMin();
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
		$result .= $env->stream()->build();
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