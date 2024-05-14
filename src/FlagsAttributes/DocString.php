<?php

namespace Flags\FlagsAttributes;

#[\Attribute]
class DocString {
	public string $doc;
	public function __construct(string $format, array $args = []){
		if( empty($args) ){
			$this->doc = $format;
			return;
		}

		$this->doc = vsprintf($format, $args);
	}
}
