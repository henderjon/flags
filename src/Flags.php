<?php

namespace Flags;

use Flags\FlagsAttributes\DocString;

/**
 * Flags is a simple command-line flag parser.
 */
class Flags {
	private const NULL_PLACEHOLDER = "NULL";
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
			throw new FlagsException("", $this->printAttrs($refObj));
			exit(0);
		}

		foreach ($refObj->getProperties() as $param) {
			try {
				$val = $this->getArgValue($this->cl, $param, $args)($this->cl, $refObj);
				$param->setValue($this->cl, $val);
			// }catch(FlagsException $e){
			// 	throw new FlagsException($e->getMessage().PHP_EOL, $this->printAttrs($refObj));
			// }catch(\ErrorException $e){
			// 	throw new FlagsException($e->getMessage());
			// }catch(\Error $e){
			// 	throw new FlagsException($e->getMessage());
			}catch(\Throwable $e){ // this should catch errors thrown by the shadow method
				// echo $e->getMessage() . PHP_EOL;
				// this error is long and verbose because the gotcha here is that contagious nullability of complex types' shadow methods
				// $new = "missing function to convert value for -{$param->getName()} \nensure all types (including defaults) match \nif using nullables, ensure function accepts/returns nullable types" . PHP_EOL;
				throw new FlagsException($e->getMessage().PHP_EOL, $this->printAttrs($refObj));
			}
		}

		return $this->cl;
	}

	private function getArgValue(object $obj, \ReflectionProperty $property, array $options):callable{
		foreach($options as $i => $arg){

			// searching the given args for the property name
			if(strpos($arg, "-{$property->getName()}") === 0 ||
				strpos($arg, "--{$property->getName()}") === 0) {

				// handle -foo=bar
				if(false !== ($pos = strpos($arg, "="))){
					$name = substr($arg, 0, $pos);
					$value = substr($arg, ($pos + 1));

				// handle -foo; boolean values must be set with '='
				}else if($property->getType()->getName() == "boolean" ||
					$property->getType()->getName() == "bool"){
						$value = true;

				// handle -foo bar; NOT for bools
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
						// if the type is not a primitive, we assume there is a shadow method
						break;

				};
				return $this->getShadowCallback($property->getName(), $value, $property->isDefault());
			}
		}

		if($property->hasDefaultValue()){
			// default values should already be typed; might not need to re-assign it ... ?
			return $this->getShadowCallback($property->getName(), $property->getValue($obj), $property->isDefault());
		}

		throw new FlagsException("missing value for -{$property->getName()}");
	}

	private function getShadowCallback(string $propertyName, mixed $value, bool $isDefault):callable{
		return function(object $inst, \ReflectionObject $refObj)use($propertyName, $value, $isDefault):mixed{
			/** Check for a shadow method with the same name as the property. If it exists, call it with the value.*/
			if( $refObj->hasMethod($propertyName)){
				$method = $refObj->getMethod($propertyName);
				return $method->invoke($inst, $value);
			}
			/** If the value was not set, throw an exception. */
			if(empty($value) && !$isDefault){
				throw new FlagsException("missing value for -{$propertyName}");
			}

			/** If the shadow method does not exist, return the value. */
			return $value;
		};
	}

	public function getDocs():string{
		$refObj = new \ReflectionObject($this->cl);
		return $this->printAttrs($refObj);
	}

	private function printAttrs(\ReflectionObject $refObj):string{
		$doc = [];
		$attr = $refObj->getAttributes(DocString::class);
		if( !empty($attr) ){
			$doc["usage"] = PHP_EOL."{$attr[0]->newInstance()->doc}";
		}else{
			$doc["usage"] = PHP_EOL."no usage provided";
		}

		foreach ($refObj->getProperties() as $property) {
			$default = "";
			if($property->hasDefaultValue()){
				$default = match($property->getType()->getName()){
					"bool", "boolean" => "default: ".($property->getDefaultValue() ? "TRUE" : "FALSE"),
					"string" => "default: \"".print_r($property->getDefaultValue(), true)."\"",
					"int", "integer",
					"double", "float" => "default: {$property->getDefaultValue()}",
					"array" => "default: ".str_replace(["    ", "\n"], [" ", ""], print_r($property->getDefaultValue(), true))."",
					"object" => "default: object",
					"NULL" => "default: NULL",
					default => "default: {$property->getDefaultValue()}",
				};
			}

			$docString = "no documentation provided";
			$attr = $property->getAttributes(DocString::class);
			if( !empty($attr) ){
				$docString = $attr[0]->newInstance()->doc;
			}

			$null = "";
			if($property->getType()->allowsNull()){
				$null = "?";
			}
			$doc[$property->getName()] = sprintf(
				"-%s (%s%s) %s \n  %s",
				$property->getName(),
				$null,
				$property->getType()->getName(),
				$default,
				$docString,
			);
		}

		foreach($doc as $param => $msg){
			if( $refObj->hasMethod($param)){
				$docString = "";
				$method = $refObj->getMethod($param);
				$attr = $method->getAttributes(DocString::class);
				if( !empty($attr) ){
					$docString = "\n  (function) => {$attr[0]->newInstance()->doc}";
				}

				if(array_key_exists($method->getName(), $doc)){
					$doc[$method->getName()] .= $docString;
				}
			}
		}
		return implode(PHP_EOL.PHP_EOL, $doc).PHP_EOL.PHP_EOL;
	}

}
