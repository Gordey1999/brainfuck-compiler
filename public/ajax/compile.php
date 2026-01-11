<?php

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED);

require_once $_SERVER['DOCUMENT_ROOT'] . '/brainfuck/lib/autoload.php';

use Gordy\Brainfuck\BigBrain;

$request = json_decode(file_get_contents('php://input'), true);

$log = "compiling...\n";

$debug = false;

try
{
	$uglify = $request['uglify'];
	$code = $request['code'];

	$tokens = BigBrain\Parser\TokenSplitter::parse($code);
	$tokenStream = new BigBrain\Parser\TokenStream($tokens);
	$parser = new BigBrain\Parser\Parser($tokenStream);
	$program = $parser->parse();

	$precompileEnv = BigBrain\Environment::makeForPrecompile($uglify, 100, 500, 256);
	$program->compile($precompileEnv);

	$registrySize = $precompileEnv->processor()->computedRegistrySize();
	$memorySize = $precompileEnv->memory()->computedMemorySize();
	$arraysMemorySize = $precompileEnv->arraysMemory()->computedMemorySize();

	$log .= "registry size computed: $registrySize\n";
	$log .= "stack size computed: $memorySize\n";
	$log .= "arrays stack size computed: $arraysMemorySize\n";

	$env = BigBrain\Environment::makeForRelease($uglify, $registrySize, $memorySize, $arraysMemorySize);
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
	$token = $e->getToken();

	$message = $debug ?
		sprintf(
			"%s\n\n\n\ndebug info:\n%s(%s)\n\ntrace:\n%s",
			$e->getMessage(),
			$e->getFile(),
			$e->getLine(),
			$e->getTraceAsString()
		)
		: $e->getMessage();

	$result = [
		'status' => 'error',
		'message' => $message,
		'position' => [
			'start' => $token->index(),
			'length'  => mb_strlen($token->value()),
		],
	];
}
catch (\Throwable $e)
{
	echo sprintf(
		"%s\n%s(%s)\ntrace:\n%s",
		$e->getMessage(),
		$e->getFile(),
		$e->getLine(),
		$e->getTraceAsString()
	);
	die;
}

echo json_encode($result);