<?php

require_once 'vendor/autoload.php';

use Flags\FlagsAttributes\DocString;

#[DocString("Usage: example.php [options]")]
class options {
	#[DocString("foo is an arg")]
	public string $foo;

	#[DocString("bar is another arg")]
	public int $bar = 5;

	#[DocString("fizz is a third arg")]
	public bool $fizz = false;

	#[DocString("buzz is an arg with a shadow method")]
	public string $buzz;

	#[DocString("the value of buzz will get uppercased")]
	public function buzz(string $v):string{
		return strtoupper($v);
	}
}

$options = (new Flags\Flags(new options))->parse($argv);
var_dump($options->getAll());
