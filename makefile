
.PHONY: check
check:
	composer validate

.PHONY: phpunit
phpunit:
	vendor/bin/phpunit --bootstrap vendor/autoload.php

.PHONY: coverage
coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --bootstrap vendor/autoload.php

.PHONY: test
test: check phpunit

