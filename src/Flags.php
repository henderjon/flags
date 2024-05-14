<?php

namespace Flags;

use Flags\FlagsAttributes\DocString;

/**
 * Flags is a simple command-line flag parser.
 */
class Flags {
	private ?array $argv = null;

	public function __construct(
		private readonly object $cl,
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
			$this->printAttrs($refObj);
			exit(1);
		}

		$this->argv = [];
		foreach ($refObj->getProperties() as $param) {
			try {
				$this->argv[$param->getName()] = $this->populateParam($this->cl, $param, $args);
			}catch(FlagsException $e){
				echo $e->getMessage() . PHP_EOL;
				$this->printAttrs($refObj);
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

		}
		return $final;
	}

	private function printAttrs(\ReflectionObject $refObj){
		$doc = [];
		$attr = $refObj->getAttributes(DocString::class);
		if( !empty($attr) ){
			$doc["usage"] = PHP_EOL."{$attr[0]->newInstance()->doc}";
		}else{
			$doc["usage"] = PHP_EOL."!no usage provided";
		}

		foreach ($refObj->getProperties() as $param) {
			$default = "";
			if($param->hasDefaultValue()){
				if($param->getType()->getName() == "bool"){
					$default = "default: ".($param->getDefaultValue() ? "true" : "false");
				}else{
					$default = "default: {$param->getDefaultValue()}";
				}
			}

			$docString = "!no documentation provided";
			$attr = $param->getAttributes(DocString::class);
			if( !empty($attr) ){
				$docString = $attr[0]->newInstance()->doc;
			}

			$doc[$param->getName()] = sprintf(
				"-%s (%s) %s \n  %s",
				$param->getName(),
				$param->getType()->getName(),
				$default,
				$docString,
			);
		}

		foreach ($refObj->getMethods() as $method) {
			$docString = "";
			$attr = $method->getAttributes(DocString::class);
			if( !empty($attr) ){
				$docString = "\n  {$attr[0]->newInstance()->doc}";
			}

			if(array_key_exists($method->getName(), $doc)){
				$doc[$method->getName()] .= $docString;
			}
		}
		echo implode(PHP_EOL.PHP_EOL, $doc).PHP_EOL;
	}

}
