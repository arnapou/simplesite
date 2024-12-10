Arnapou simplesite
====================

![pipeline](https://gitlab.com/arnapou/project/simplesite/badges/main/pipeline.svg)
![coverage](https://gitlab.com/arnapou/project/simplesite/badges/main/coverage.svg)


Links
--------------------

> Demo 👉️ http://simplesite.arnapou.net <br>
> Article 👉️ http://arnapou.net/php/site_phar/ <br>
> Phar file 👉️ [bin/simplesite.phar](bin/simplesite.phar)


Docker
--------------------

Web server
--------------------

You can directly use our php image if you want something working out of the box :
- `registry.gitlab.com/arnapou/docker/php:8.4-frankenphp`

We recommend 
- [FrankenPHP](https://frankenphp.dev/)
- [Caddy](https://caddyserver.com/) with [php-fpm](https://hub.docker.com/_/php/tags?name=fpm) backend

If you want to use [Apache](https://hub.docker.com/_/php/tags?name=apache), we suggest a `.htaccess` like this :

```apacheconf
RewriteEngine On

DirectorySlash Off

FileETag MTime Size
Options -Indexes -MultiViews -ExecCGI +FollowSymLinks +SymLinksIfOwnerMatch

RewriteCond %{REQUEST_FILENAME} -d
RewriteRule . index.php [L,QSA]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L,QSA]
```


Php versions
--------------------

| Date       | Ref       | 8.4 | 8.3 | 8.2 | 8.1 | 8.0 | 7.2 |
|------------|-----------|:---:|:---:|:---:|:---:|:---:|:---:|
| 25/11/2024 | 7.x, main |  ×  |     |     |     |     |     |
| 26/11/2023 | 6.x       |     |  ×  |     |     |     |     |
| 11/10/2023 | 5.x       |     |     |  ×  |     |     |     |
| 19/12/2022 | 4.x       |     |     |  ×  |     |     |     |
| 30/01/2022 | 3.x       |     |     |     |  ×  |     |     |
| 15/05/2021 | 2.x       |     |     |     |     |  ×  |     |
| 07/12/2019 | 1.x       |     |     |     |     |     |  ×  |
