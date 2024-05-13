<?php

require_once 'vendor/autoload.php';

use Flags\FlagDocInterface;

class getopt implements FlagDocInterface {
	public string $foo;
	public int $bar = 5;
	public bool $fizz = false;
	public string $buzz;

	public function buzz(string $v):string{
		return strtoupper($v);
	}

	public function doc():string{
		$str = "Usage: php test.php [options]" . PHP_EOL.PHP_EOL;
		$str .= "-foo (string) \n  'foo' is an arg" . PHP_EOL;
		$str .= "-bar (int) = 5" . PHP_EOL;
		$str .= "-fizz (bool) = false" . PHP_EOL;
		$str .= "-buzz (string) " . PHP_EOL;
		return $str;
	}
}

$f = new Flags\Flags(new getopt);
$args = $f->parse($argv);



$foo = "";
$bar = "";
$fizz = "";
$buzz = "";
$f->assignByRef("foo", $foo);
$f->assignByRef("bar", $bar);
$f->assignByRef("fizz", $fizz);
$f->assignByRef("buzz", $buzz);
drop(
	$foo,
	$bar,
	$fizz,
	$buzz,
);
