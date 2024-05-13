<?php

class Args {
	public function __construct(
		private readonly \Closure $fn,
	) {}

	public function parse(array $args): array {
		$refFunction = new ReflectionFunction($this->fn);

		$args = $this->preProcessArgs($args);

		$paramsByName = [];
		foreach ($refFunction->getParameters() as $param) {
			$paramsByName[$param->getName()] = $param;
		}

		return $this->populateParams($paramsByName, $args);
	}

	private function populateParams(array $params, array $givenArgs):array{
		$argv = [];
		foreach ($params as $param) {
			if( !array_key_exists($param->getName(), $givenArgs) ){
				if( !$param->isOptional() ){
					throw new Exception("Missing required parameter: {$param->getName()}");
				}

				if($param->isDefaultValueAvailable()){
					$argv[$param->getName()] = $param->getDefaultValue();
				}
				continue;
			}

			$type = $param->getType();
			if(!$type->isBuiltin()){
				throw new Exception("Unsupported type: {$param->getType()}");
			}else if($this->isCallable($param)){
				$v = $param($givenArgs[$param->getName()]);
			}else{
				$v = $givenArgs[$param->getName()];
				match($type->getName()){
					"boolean", "bool",
					"integer", "int",
					"float", "double",
					"string" => settype($v, $type->getName()),
					default => throw new Exception("Unsupported type: {$type->getName()}"),
				};
			}

			$argv[$param->getName()] = $v;
		}
		return $argv;
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
