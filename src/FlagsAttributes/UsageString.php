<?php

namespace Flags\FlagsAttributes;

#[\Attribute]
class UsageString {
	public function __construct(
		public readonly string $doc,
	){}

}
