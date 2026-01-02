<?php

spl_autoload_register(function($className) {

	$parts = explode('\\', $className);
	if(!isset($parts[0]) || $parts[0] != 'ParagonIE') return;
	array_shift($parts);

	$path = dirname(__FILE__) . "/" .implode('/' , $parts) . ".php";

	//	echo "\nPATH: $path\n";
	if(file_exists($path)) require_once $path;

});
