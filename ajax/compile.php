<?php
require_once __DIR__ . '/lib/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED);

use Gordy\Brainfuck\Compiler;
use Gordy\Brainfuck\Compiler\OutputStream;

echo '<pre>';

$stream = new OutputStream();
$processor = new Compiler\Processor($stream);

$a = $processor->reserve();

$processor->addConstant($a, 234);
$processor->printNumber($a);

$program = <<<CODE
Const a = 254;
Echo "Hello, World! {a} \n" . a;
CODE;

$parser = new Compiler\Parser();
$parser->parse($program);
die;


//$b = $processor->reserve();
//$c = $processor->reserve();
//$d = $processor->reserve();
//
//$processor->addConstant($a, 255);
//$processor->addConstant($b, 2);
//$processor->divide($a, $b, $c, $d);

echo '<pre>';
print_r($stream->build());
echo '</pre>';