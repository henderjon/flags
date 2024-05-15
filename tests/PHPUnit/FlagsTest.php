<?php

require_once "vendor/autoload.php";

class user {
	public function __construct(
		public readonly string $name,
	){}
}

class FlagsTest extends PHPUnit\Framework\TestCase {
	public function test_parse(){

		$c = new class(){
			public string $foo;
			public string $fizz;
			public bool $soup;
			public int $taco;
			public int $burrito = 5;
			public function burrito(string $v):int{
				return 5*intval($v);
			}
		};

		$given = ["script-name", "-foo", "bar", "-fizz=buzz", "-soup", "-taco", "7"];

		$result = (new \Flags\Flags($c))->parse($given);
		// check the return value
		$this->assertSame("bar", $result->foo);
		$this->assertSame("buzz", $result->fizz);
		$this->assertSame(true, $result->soup);
		$this->assertSame(7, $result->taco);
		$this->assertSame(25, $result->burrito);
		// check the reference
		$this->assertSame("bar", $c->foo);
		$this->assertSame("buzz", $c->fizz);
		$this->assertSame(true, $c->soup);
		$this->assertSame(7, $c->taco);
		$this->assertSame(25, $c->burrito);
	}

	public function test_parse_with_complex_types(){

		$c = new class(){
			public user $person;
			public function person(string $v):user{
				return new user($v);
			}

			public int $age = 2;
			public function age(string $v):int{
				return $this->age * intval($v);
			}

			public ?user $spouse = null;
			public function spouse(string $v):user{
				return new user($v);
			}
		};

		$given = ["script-name", "-person", "jack", "-spouse", "jill"];

		$result = (new \Flags\Flags($c))->parse($given);
		// check the return value
		$this->assertEquals(new user("jack"), $result->person);
		$this->assertEquals(new user("jill"), $result->spouse);
		// check the reference
		$this->assertEquals(new user("jack"), $c->person);
		$this->assertEquals(new user("jill"), $c->spouse);
	}

	public function test_parse_with_nullable_complex_types(){

		$c = new class(){
			public user $person;
			public function person(string $v):user{
				return new user($v);
			}

			public int $age = 2;
			public function age(string $v):int{
				return $this->age * intval($v);
			}

			public ?user $spouse = null;
			public function spouse(?string $v):?user{
				if( is_null($v) ){
					return null;
				}
				return new user($v);
			}
		};

		$given = ["script-name", "-person", "jack", "--age", "5"];

		$result = (new \Flags\Flags($c))->parse($given);
		// check the return value
		$this->assertEquals(new user("jack"), $result->person);
		$this->assertSame(10, $result->age);
		$this->assertSame(null, $result->spouse);
		// check the reference
		$this->assertEquals(new user("jack"), $c->person);
		$this->assertSame(10, $c->age);
		$this->assertSame(null, $c->spouse);
	}
}
