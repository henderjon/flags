<?php

namespace Flags;

class FlagsException extends \Exception {
	public function __construct(
		public readonly string $error,
		public readonly string $additionalDocs = "",
		?\Throwable $previous = null,
	){
		parent::__construct($error, 1, $previous);
	}

	public function getAdditionalDocs():string{
		return $this->additionalDocs;
	}

	public function __toString():string{
		$final = $this->message;
		if($final !== ""){
			$final .= PHP_EOL;
		}
		return $final . $this->additionalDocs;
	}

	public function print():void{
		echo $this;
	}

}
