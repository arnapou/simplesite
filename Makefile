
build: update
	php -d "phar.readonly=Off" ./bin/box build

update:
	php bin/composer update

php-cs-fixer:
	php bin/php-cs-fixer fix --config=.php_cs --verbose --using-cache=no
