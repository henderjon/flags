<?php

require_once 'vendor/autoload.php';
require_once 'Args.php';

// $f = function(
// 	public string $foo,
// 	public int $bar = 5,
// 	public bool $fizz = false,
// ):void{};

class ags {
	public string $foo;
	public int $bar = 5;
	public bool $fizz = false;

	public function buzz($v){
		return strtoupper($v);
	}
}

$args = (new Args(new ags))->parse($argv);

drop($args);

// foreach ($params as $param) {
// 	echo $param->getName() . PHP_EOL;
// 	echo $param->getType() . PHP_EOL;
// 	echo $param->isOptional() . PHP_EOL;
// 	if( $param->isDefaultValueAvailable() ){
// 		echo $param->getDefaultValue() . PHP_EOL;
// 	}
// 	echo PHP_EOL;
// }
