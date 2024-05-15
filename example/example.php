<?php

require_once 'vendor/autoload.php';

use Flags\FlagsAttributes\DocString;

class user {
	public function __construct(
		public readonly string $name,
	){}
}

#[DocString("Usage: example.php [options]")]
class options {
	#[DocString("foo is an arg")]
	public string $foo = "a string";

	#[DocString("bar is another arg")]
	public int $bar = 5;

	#[DocString("fizz is a third arg")]
	public bool $fizz = false;

	#[DocString("buzz is an arg with a shadow method")]
	public ?user $human;

	#[DocString("the value of buzz will get uppercased")]
	public function human(string $v):user{
		return new user($v);
	}
}

$options = (new Flags\Flags(new options))->parse($argv);
var_dump($options);
