<?php

class Args {
	public function __construct(
		private readonly object $cl,
	) {}

	public function parse(array $args): array {
		$refObj = new ReflectionObject($this->cl);
		$args = $this->preProcessArgs($args);

		$argv = [];
		foreach ($refObj->getProperties() as $param) {
			$argv[$param->getName()] = $this->populateParam($this->cl, $param, $args);
		}
		// foreach ($refObj->getMethods() as $method) {
		// 	$argv[$param->getName()] = $this->populateMethod($this->cl, $method, $args);
		// }
		foreach (array_keys($args) as $val) {
			try{
				$method = $refObj->getMethod($val);
				$argv[$param->getName()] = $this->populateMethod($this->cl, $method, $args);
			}catch(ReflectionException $e){
				// throw new Exception("Invalid method: {$val}");
			}
		}

		return $argv;
	}

	private function populateParam(object $obj, ReflectionProperty $param, array $givenArgs):mixed{
		if( !array_key_exists($param->getName(), $givenArgs) ){
			if( !$param->hasDefaultValue() ){
				throw new Exception("Missing required parameter: {$param->getName()}");
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
			default => throw new Exception("Unsupported type: {$type->getName()}"),
		};

		return $v;
	}

	private function populateMethod(object $obj, ReflectionMethod $method, array $givenArgs):mixed{
		// if( !array_key_exists($method->getName(), $givenArgs) ){
		// 	throw new Exception("Missing required parameter: {$method->getName()}");
		// }

		return $method->invoke($obj, $givenArgs[$method->getName()]);
	}

	private function preProcessArgs(array $args):array{
		$args = array_slice($args, 1);
		if(count($args) % 2 !== 0){
			throw new Exception("Invalid number of arguments");
		}

		$clean = [];
		for($n = 0; $n < count($args); $n++){
			$clean[ltrim($args[$n], "-")] = $args[$n+=1];
		}
		return $clean;
	}

	private function isCallable(ReflectionParameter $val){
		$reflectionType = $val->getType();

		if (!$reflectionType) return false;

		$types = $reflectionType instanceof ReflectionUnionType
			? $reflectionType->getTypes()
			: [$reflectionType];

		return in_array('callable', array_map(fn(ReflectionNamedType $t) => $t->getName(), $types));
	}

	private function forceType(){}

	// private function processParam(ReflectionParameter $param): array {
	// 	return [
	// 		'name' => $param->getName(),
	// 		'type' => $param->getType(),
	// 		'isOptional' => $param->isOptional(),
	// 		'isDefaultValueAvailable' => $param->isDefaultValueAvailable(),
	// 		'defaultValue' => $param->getDefaultValue(),
	// 	];
	// }
}
