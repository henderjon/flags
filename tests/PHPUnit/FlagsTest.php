<?php

require_once "vendor/autoload.php";

class FlagsTest extends PHPUnit\Framework\TestCase {

	public function test_parse(){

		$c = new class(){
			public string $foo;
			public string $fizz;
			public bool $soup;
			public int $taco;
			public int $burrito = 5;
			public function doc():string{
				return "this is a test";
			}
		};

		$given = ["script-name", "-foo", "bar", "-fizz=buzz", "-soup", "-taco", "7"];

		$result = (new \Flags\Flags($c))->parse($given);
		// check the return value
		$this->assertSame("bar", $result->foo);
		$this->assertSame("buzz", $result->fizz);
		$this->assertSame(true, $result->soup);
		$this->assertSame(7, $result->taco);
		$this->assertSame(5, $result->burrito);
		// check the reference
		$this->assertSame("bar", $c->foo);
		$this->assertSame("buzz", $c->fizz);
		$this->assertSame(true, $c->soup);
		$this->assertSame(7, $c->taco);
		$this->assertSame(5, $c->burrito);
	}

}
