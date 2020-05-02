.PHONY: help
help:
	@$(MAKE) -p : 2>/dev/null | egrep -v -e '^#' -e '^Makefile|^help' | egrep '^[[:alnum:]][[:alnum:]\.-]+\:' | sed -e 's/:.*//g' | sort

# ----------------------------------------

build: update
	php -d "phar.readonly=Off" ./bin/box build

update:
	php bin/composer update

php-cs-fixer:
	php bin/php-cs-fixer fix --config=.php_cs --verbose --using-cache=no
