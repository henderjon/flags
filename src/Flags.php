<?php

namespace Flags;

use Flags\FlagsAttributes\DocString;
use ReflectionObject;
use ReflectionProperty;
use Throwable;

/**
 * Flags is a simple command-line flag parser.
 *
 * Given an object whose properties represent the possible/expected args for the parent script. Use the Attribute
 * `DocString` to provide documentation to a given script.
 *
 * bool, int, float, and string are all supported as is including being nullable.
 *
 * To process more complicated types or parse the values of the types listed above, a "shadow method"
 * can be provided meaning that a method whose name is identical to that of the property that takes
 * the value providee as an arg and returns the value post-processing. This can be used for simple
 * text processing (this "foo,bar,buzz" into an array) or instantiating a more complex object.
 *
 * All values should have a value of some kind. Null can be used as a "not set" default if need be.
 *
 * Special notes:
 * - To set a bool true simply use it (e.g. -var). To set its value it is required to use the equal sign (e.g. -var=false)
 * - The args `-help` or `--help` will always print DocStings
 * - When combing custom types, nullables, default values, and shadow methods, it can be tricky to make sure that
 *   everything that OUGHT to be nullable, is so
 * - errors/exception in the vein of Argument #1 ($v) must be of type $TYPE, null given that means that the expected
 *   type of the property, shadow function parameter, and shadow function return type don't match. Specifically,
 *   nullable types require everything else to be nullable, especially when it's a complex type that is nullable and
 *   defaults to null as a value.
 *
 */
class Flags {
	private ?array $argv = null;

    /**
     * @param object $cl This object represents the potential args for the parent script.
     */
	public function __construct(
		private readonly object $cl,
	) {}

	/**
	 * Parse the given arguments and return an object with the parsed flags. In
	 * practice, this method should accept $argv from the main script. If not,
     * there is a flag to ignore the first key in the array (what would have been
     * the script name).
	 */
	public function parse(array $args, bool $sName = true): array|object {
        // don't parse twice
		if(!is_null($this->argv)){
			return $this->argv;
		}

		$refObj = new ReflectionObject($this->cl);

		if($sName){
			$args = array_slice($args, 1); // remove script name
		}

		if(in_array("-help", $args) || in_array("--help", $args)){
			throw new FlagsException("", $this->printAttrs($refObj));
		}

		foreach ($refObj->getProperties() as $param) {
			try {
				$val = $this->getArgValue($this->cl, $param, $args)($this->cl, $refObj);
				$param->setValue($this->cl, $val);
			}catch(Throwable $e){ // this should catch errors thrown by the shadow method
				throw new FlagsException($e->getMessage().PHP_EOL, $this->printAttrs($refObj));
			}
		}

		return $this->cl;
	}

	private function getArgValue(object $obj, ReflectionProperty $property, array $options):callable{
		foreach($options as $i => $arg){

			// searching the given args for the property name
			if(str_starts_with($arg, "-{$property->getName()}") ||
                str_starts_with($arg, "--{$property->getName()}")) {

				// handle -foo=bar
				if(false !== ($pos = strpos($arg, "="))){
//					$name = substr($arg, 0, $pos);
					$value = substr($arg, ($pos + 1));
					if(!$value){
						throw new FlagsException("missing value for -{$property->getName()}");
					}

				// handle -foo; boolean values must be set with '='
				}else if($property->getType()?->getName() === "boolean" ||
					$property->getType()?->getName() === "bool"){
						$value = true;

				// handle -foo bar; NOT for bool
				}else if( array_key_exists($i+1, $options) ){
					if($options[$i+1][0] === "-"){
						throw new FlagsException("missing value for -{$property->getName()}; to pass a value that starts with a dash, use the '=' syntax: -{$property->getName()}=value");
					}
					$value = $options[$i+1];

				// something went wrong
				}else {
					throw new FlagsException("missing required -{$property->getName()}");
				}

				$type = $property->getType();
				switch($type?->getName()){
					case "boolean":
					case "bool":
                        $value = match(true){
                            is_bool($value) => $value, // handle -foo; previously set
                            strtolower($value) === "false", $value === "0" => false, // handle -foo=false
                            strtolower($value) === "true", $value === "1" => true, // handle -foo=true
                            default => throw new FlagsException("cannot parse '{$value}' as bool for -{$property->getName()}"),
                        };

						break;
					case "integer":
					case "int":
						if(!ctype_digit($value)){
							throw new FlagsException("cannot parse '{$value}' as int for -{$property->getName()}");
						}
						$value = (int)$value;
						break;
					case "float":
					case "double":
						if(!is_numeric($value)){
							throw new FlagsException("cannot parse '{$value}' as float for -{$property->getName()}");
						}
						$value = (float)$value;
						break;
					case "string":
						$value = (string)$value;
						break;
					default:
						// if the type is not a primitive, we assume there is a shadow method
						break;

				}
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
		return static function(object $inst, ReflectionObject $refObj)use($propertyName, $value, $isDefault):mixed{
			/** Check for a shadow method with the same name as the property. If it exists, call it with the value.*/
			if( $refObj->hasMethod($propertyName)){
                return $refObj->getMethod($propertyName)->invoke($inst, $value);
			}
			/** If the value was not set, throw an exception. */
			if(empty($value) && !$isDefault){
				throw new FlagsException("missing value for -{$propertyName}");
			}

			/** If the shadow method does not exist, return the value. */
			return $value;
		};
	}

    /**
     * getDocs pretty prints the expected args based on the shape of the object given to __construct()
     * @return string
     */
	public function getDocs(string $message = ""):string{
		$refObj = new ReflectionObject($this->cl);
		return $this->printAttrs($refObj, $message);
	}

	private function printAttrs(ReflectionObject $refObj, string $message = ""):string{
		$doc = [];
		$attr = $refObj->getAttributes(DocString::class);
		if( !empty($attr) ){
			$doc["usage"] = PHP_EOL. ($attr[0]->newInstance()->doc);
		}else{
			$doc["usage"] = PHP_EOL."no usage provided";
		}

		foreach ($refObj->getProperties() as $property) {
			$default = "";
			if($property->hasDefaultValue()){
				$default = match($property->getType()?->getName()){
					"int", "integer",
					"double", "float" => "default: {$property->getDefaultValue()}",
					"bool", "boolean" => "default: ".($property->getDefaultValue() ? "TRUE" : "FALSE"),
					"string" => "default: \"".print_r($property->getDefaultValue(), true)."\"", // why am I print_r-ing this?
					"array"  => "default: ".str_replace(["    ", "\n"], [" ", ""], print_r($property->getDefaultValue(), true)),
					"object" => "default: object",
					"NULL"   => "default: NULL",
					default  => "default: {$property->getDefaultValue()}",
				};
			}

			$docString = "no documentation provided";
			$attr = $property->getAttributes(DocString::class);
			if( !empty($attr) ){
				$docString = $attr[0]->newInstance()->doc;
			}

			$null = "";
			if($property->getType()?->allowsNull()){
				$null = "?";
			}
            $propertyName = $property->getName();
			$doc[$propertyName] = sprintf(
				"-%s (%s%s) %s \n  %s",
                $propertyName,
				$null,
				$property->getType()?->getName(),
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

        if($message){
            $doc["additional"] = sprintf("-----".PHP_EOL."%s", $message);
        }

		return implode(PHP_EOL.PHP_EOL, $doc).PHP_EOL.PHP_EOL;
	}

}
