<?php

spl_autoload_register(function($className) {
	$namespace = 'Gordy\\Brainfuck\\';
	$namespaceLength = strlen($namespace);

	if (substr($className, 0, $namespaceLength) === $namespace)
	{
		$classNameRelative = substr($className, $namespaceLength);
		$classRelativePath =  str_replace('\\', '/', $classNameRelative) . '.php';
		$classFullPath = __DIR__ . '/' . $classRelativePath;

		if (file_exists($classFullPath))
		{
			require_once $classFullPath;
		}
	}
});
