<?php

require_once "vendor/autoload.php";

class FlagsTest extends PHPUnit\Framework\TestCase {

	function test_preProcessArgs(){
		$inst       = new \Flags\Flags($this->createMock(\Flags\FlagDocInterface::class));
		$reflection = new ReflectionClass($inst);

		$method = $reflection->getMethod("preProcessArgs");
		$method->setAccessible(true);

		$table = [
			[
				"given" => ["script-name", "-foo", "bar", "-fizz=buzz", "-soup"],
				"expected" => ["foo" => "bar", "fizz" => "buzz", "soup" => true],
			],
			[
				"given" => ["script-name", "-soup", "-foo", "bar", "-fizz=buzz"],
				"expected" => ["soup" => true, "foo" => "bar", "fizz" => "buzz"],
			],
			[
				"given" => ["script-name", "-soup", "-bbq", "-foo", "bar", "-fizz=buzz"],
				"expected" => ["soup" => true, "bbq" => true, "foo" => "bar", "fizz" => "buzz"],
			],
			[
				"given" => ["script-name", "-soup", "-bbq", "false", "-foo", "bar", "-fizz=buzz"],
				"expected" => ["soup" => true, "bbq" => 'false', "foo" => "bar", "fizz" => "buzz"],
			],
			[
				"given" => ["script-name", "-soup", "-bbq", "false", "-foo", "bar", "-fizz=buzz", "-taco"],
				"expected" => ["soup" => true, "bbq" => 'false', "foo" => "bar", "fizz" => "buzz", "taco" => true],
			],
			[
				"given" => ["script-name", "-soup", "-bbq=false", "-foo", "bar", "-fizz=buzz", "-taco"],
				"expected" => ["soup" => true, "bbq" => 'false', "foo" => "bar", "fizz" => "buzz", "taco" => true],
			],
		];

		foreach($table as $key => $test){
			$result = $method->invokeArgs($inst, [$test["given"]]);
			$this->assertEquals($test["expected"], $result, sprintf("test failed: %d", $key));
		}
	}

	function test_parse(){

		$c = new class(){
			private string $foo;
			private string $fizz;
			private bool $soup;
			private int $taco;
			private int $burrito = 5;
			public function doc():string{
				return "this is a test";
			}
		};

		$given = ["script-name", "-foo", "bar", "-fizz=buzz", "-soup", "-taco", "7"];

		$result = (new \Flags\Flags($c))->parse($given);
		$this->assertSame("bar", $result->foo);
		$this->assertSame("buzz", $result->fizz);
		$this->assertSame(true, $result->soup);
		$this->assertSame(7, $result->taco);
		$this->assertSame(5, $result->burrito);
	}

}
