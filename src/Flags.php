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

		$args = array_slice($args, 1); // remove script name
		if(in_array("-help", $args) || in_array("--help", $args)){
			$this->printAttrs($refObj);
			exit(0);
		}

		foreach ($refObj->getProperties() as $param) {
			try {
				$param->setValue($this->cl, $this->getArgValue($this->cl, $param, $args));
			}catch(FlagsException $e){
				echo $e->getMessage() . PHP_EOL;
				$this->printAttrs($refObj);
				exit(1);
			}
		}

		foreach (array_keys($args) as $val) {
			if( $refObj->hasMethod($val) ){
				$method = $refObj->getMethod($val);
				try {
					$param = $refObj->getProperty($method->getName());
					$param->setValue($this->cl, $method->invoke($this->cl, $args[$method->getName()]));
				}catch(\ReflectionException $e){
					// ignore method overload property not found
				}
			}
		}
		return $this->cl;
	}

	private function getArgValue(object $obj, \ReflectionProperty $property, array $options):mixed{
		foreach($options as $i => $arg){

			if(strpos($arg, "-{$property->getName()}") === 0 ||
				strpos($arg, "--{$property->getName()}") === 0) {

				// handle -foo=bar
				if(false !== ($pos = strpos($arg, "="))){
					$name = substr($arg, 0, $pos);
					$value = substr($arg, ($pos + 1));

				// handle -foo
				}else if($property->getType()->getName() == "boolean" ||
					$property->getType()->getName() == "bool"){ // boolean flag must be set with '='
						$value = true;

				// handle -foo bar
				}else if( array_key_exists($i+1, $options) ){
					$value = $options[$i+1];

				// something went wrong
				}else {
					throw new FlagsException("missing required -{$property->getName()}");
				}

				$type = $property->getType();
				switch($type->getName()){
					case "boolean":
					case "bool":
						if(strtolower($value) == "false" || $value == "0"){
							$value = false;
						}else if(strtolower($value) == "true" || $value == "1"){
							$value = true;
						}else{
							throw new FlagsException("cannot parse '{$value}' as bool for -{$property->getName()}");
						}
						break;
					case "integer":
					case "int":
						if(!ctype_digit($value)){
							throw new FlagsException("cannot parse '{$value}' as int for -{$property->getName()}");
						}
						settype($value, "int");
						break;
					case "float":
					case "double":
						if(!is_numeric($value)){
							throw new FlagsException("cannot parse '{$value}' as float for -{$property->getName()}");
						}
						settype($value, "float");
						break;
					case "string":
						settype($value, "string");
						break;
					default:
						throw new FlagsException("unsupported type: {$type->getName()}");
						break;

				};
				return $value;
			}
		}

		if($property->hasDefaultValue()){
			return $property->getValue($obj); // default values should already be typed
		}

		throw new FlagsException("missing value for -{$property->getName()}");
	}

	private function printAttrs(\ReflectionObject $refObj){
		$doc = [];
		$attr = $refObj->getAttributes(DocString::class);
		if( !empty($attr) ){
			$doc["usage"] = PHP_EOL."{$attr[0]->newInstance()->doc}";
		}else{
			$doc["usage"] = PHP_EOL."no usage provided";
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

			$docString = "no documentation provided";
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
		echo implode(PHP_EOL.PHP_EOL, $doc).PHP_EOL.PHP_EOL;
	}

}
