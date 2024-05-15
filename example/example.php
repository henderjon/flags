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

	#[DocString("human is an arg with a shadow method")]
	public string $human;

	#[DocString("the value of buzz will get uppercased")]
	public function human(string $v):string{
		return strtoupper($v);
	}

	#[DocString("person is an arg with a special type and shadow method")]
	public ?user $person = null;

	#[DocString("person is an object")]
	public function person(?string $v):?user{
		if( empty($v) ){
			return null;
		}
		return new user($v);
	}

}

$result = (new \Flags\Flags(new options))->parse($argv);
var_dump($result);

// $c = new class(){
// 	// public user $person;
// 	// public function person(string $v):user{
// 	// 	return new user($v);
// 	// }

// 	// public int $age = 2;
// 	// public function age(string $v):int{
// 	// 	return $this->age * intval($v);
// 	// }

// 	public ?user $spouse = null;
// 	public function spouse(string $v):user{
// 		return new user($v);
// 	}
// };

// $given = ["script-name", "-person", "jack"];
// $result = (new \Flags\Flags($c))->parse($given);
// var_dump($result);
