
.PHONY: check
check:
	composer validate

.PHONY: phpunit
phpunit:
	XDEBUG_MODE=coverage vendor/bin/phpunit --bootstrap vendor/autoload.php

.PHONY: test
test: check phpunit

