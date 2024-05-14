<?php

namespace Flags;

/**
 * Flags is a simple command-line flag parser.
 */
class Flags {
	private const DOC_METHOD_NAME = "doc";
	private ?array $argv = null;

	public function __construct(
		private readonly FlagDocInterface $cl,
	) {}

	/**
	 * Parse the given arguments and return an object with the parsed flags. In
	 * Practice, this method should accept $argv from the main script.
	 *
	 * @param array $args
	 * @return object
	 */
	public function parse(array $args): object {
		if(!is_null($this->argv)){
			return $this->argv;
		}

		$refObj = new \ReflectionObject($this->cl);

		try {
			$args = $this->preProcessArgs($args);
		}catch(FlagsException $e){
			echo $e->getMessage() . PHP_EOL;
			$this->printDoc($refObj);
			exit(1);
		}

		$this->argv = [];
		foreach ($refObj->getProperties() as $param) {
			try {
				$this->argv[$param->getName()] = $this->populateParam($this->cl, $param, $args);
			}catch(FlagsException $e){
				echo $e->getMessage() . PHP_EOL;
				$this->printDoc($refObj);
				exit(1);
			}
		}

		foreach (array_keys($args) as $val) {
			if( $refObj->hasMethod($val) ){
				$method = $refObj->getMethod($val);
				$this->argv[$param->getName()] = $method->invoke($this->cl, $args[$method->getName()]);
			}
		}

		return new class($this->argv) {
			public function __construct(private readonly array $argv){}
			public function __get(string $name):mixed{
				if( !array_key_exists($name, $this->argv) ){
					throw new FlagsException("flag '{$name}' not found");
				}

				return $this->argv[$name];
			}
			public function __isset(string $name):bool{
				return array_key_exists($name, $this->argv);
			}
			public function getAll():array{
				return $this->argv;
			}
		};
	}

	private function populateParam(object $obj, \ReflectionProperty $param, array $givenArgs):mixed{
		if( !array_key_exists($param->getName(), $givenArgs) ){
			if( !$param->hasDefaultValue() ){
				throw new FlagsException("missing required parameter: {$param->getName()}");
			}

			return $argv[$param->getName()] = $param->getValue($obj);
		}

		$type = $param->getType();
		$v = $givenArgs[$param->getName()];
		match($type->getName()){
			"boolean", "bool",
			"integer", "int",
			"float", "double",
			"string" => settype($v, $type->getName()),
			default => throw new FlagsException("unsupported type: {$type->getName()}"),
		};

		return $v;
	}

	private function preProcessArgs(array $args):array{
		$args = array_slice($args, 1); // remove script name

		$final = [];
		$previous = null;
		while( $current = array_shift($args) ){

			// pairs with equal sign
			if(false !== ($pos = strpos($current, "="))){
				$key = substr($current, 0, $pos);
				$final[ltrim($key, "-")] = substr($current, ($pos + 1));
				continue;
			}

			if(str_starts_with($current, "-")){
				// spaced bool
				if(empty($previous) || (!empty($previous) && str_starts_with($previous, "-"))){
					$final[ltrim($current, "-")] = true;
				}

				$previous = $current;
				continue;
			}

			// spaced value
			if(!empty($previous) && str_starts_with($previous, "-")){
				$final[ltrim($previous, "-")] = $current;
				$previous = null;
				continue;
			}

			// if(str_starts_with($current, "-") && !empty($previous)){
			// 	$previous = $current;
			// 	continue;
			// }
			// $key = trim($arg, " -");
			// $final[$key] = false;

			// if(false !== ($pos = strpos($arg, "="))){
			// 	$key         = substr($arg, 0, $pos);
			// 	$final[$key] = substr($arg, ($pos + 1));
			// 	continue;
			// }

			// if( in_array($arg, $values) ){
			// 	$final[$arg] = array_shift($_argv);
			// 	continue;
			// }

			// if( in_array($arg, $flags) ){
			// 	$final[$arg] = true;
			// 	continue;
			// }
		}
		return $final;

		// if(count($args) % 2 !== 0){
		// 	throw new FlagsException("missing value for flag(s); must be in key-value pairs");
		// }

		// $clean = [];
		// for($n = 0; $n < count($args); $n++){
		// 	$clean[ltrim($args[$n], "-")] = $args[$n+=1];
		// }
		// return $clean;
	}

	private function printDoc(\ReflectionObject $refObj){
		if( $refObj->implementsInterface(FlagDocInterface::class) ){
			echo PHP_EOL . $this->cl->{self::DOC_METHOD_NAME}() . PHP_EOL;
		}
	}

}
