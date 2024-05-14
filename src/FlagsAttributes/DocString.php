<?php

namespace Flags\FlagsAttributes;

#[\Attribute]
class DocString {
	public string $doc;
	public function __construct(string $format, array $args = []){
		$this->doc = vsprintf($format, $args);
	}
}
