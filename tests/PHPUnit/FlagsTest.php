<?php

require_once "vendor/autoload.php";

class FlagsTest extends PHPUnit\Framework\TestCase {

	function test_preProcessArgs(){
		$inst       = new \Flags\Flags($this->createMock(\Flags\FlagDocInterface::class));
		$reflection = new ReflectionClass($inst);

		$method = $reflection->getMethod("preProcessArgs");
		$method->setAccessible(true);

		$result = $method->invokeArgs($inst, [["script-name", "-foo", "bar", "-fizz=buzz", "-soup"]]);
		$expected = ["foo" => "bar", "fizz" => "buzz", "soup" => true];
		$this->assertEquals($expected, $result);

		$result = $method->invokeArgs($inst, [["script-name", "-soup", "-foo", "bar", "-fizz=buzz"]]);
		$expected = ["soup" => true, "foo" => "bar", "fizz" => "buzz"];
		$this->assertSame($expected, $result);

		$result = $method->invokeArgs($inst, [["script-name", "-soup", "-bbq", "-foo", "bar", "-fizz=buzz"]]);
		$expected = ["soup" => true, "bbq" => true, "foo" => "bar", "fizz" => "buzz"];
		$this->assertSame($expected, $result);

		$result = $method->invokeArgs($inst, [["script-name", "-soup", "-bbq", "false", "-foo", "bar", "-fizz=buzz"]]);
		$expected = ["soup" => true, "bbq" => 'false', "foo" => "bar", "fizz" => "buzz"];
		$this->assertSame($expected, $result);

		$result = $method->invokeArgs($inst, [["script-name", "-soup", "-bbq", "false", "-foo", "bar", "-fizz=buzz", "-taco"]]);
		$expected = ["soup" => true, "bbq" => 'false', "foo" => "bar", "fizz" => "buzz", "taco" => true];
		$this->assertSame($expected, $result);

		$result = $method->invokeArgs($inst, [["script-name", "-soup", "-bbq=false", "-foo", "bar", "-fizz=buzz", "-taco"]]);
		$expected = ["soup" => true, "bbq" => 'false', "foo" => "bar", "fizz" => "buzz", "taco" => true];
		$this->assertSame($expected, $result);
	}

}
