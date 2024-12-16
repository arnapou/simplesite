Arnapou simplesite
====================

![pipeline](https://gitlab.com/arnapou/project/simplesite/badges/main/pipeline.svg)
![coverage](https://gitlab.com/arnapou/project/simplesite/badges/main/coverage.svg)


Links
--------------------

> Demo 👉️ https://simplesite.arnapou.net/ <br>
> Article 👉️ https://arnapou.net/software/2019-phar-simplesite/ <br>
> Phar file 👉️ [bin/simplesite.phar](bin/simplesite.phar)


Docker
--------------------

You can use the provided docker image directly. 
Below is an example of a working docker compose file `compose.yaml`.

```yaml
services:
  app:
    image: registry.gitlab.com/arnapou/project/simplesite:v8
    user: 1000:1000
    
    # For local testing / dev -> http://localhost
    ports: [ "80:80" ]
    environment:
      # FrankenPHP environment variables
      #  ╰─ https://github.com/dunglas/frankenphp/blob/main/caddy/frankenphp/Caddyfile
      CADDY_GLOBAL_OPTIONS: "auto_https off" # (default)
      SERVER_NAME: ":80"                     # (default)
      # SimpleSite environment variables
      SIMPLESITE_ADMIN: "admin"              # Base path of the admin GUI.
                                             #  ╰─ http://localhost/admin/
                                             # Default: "" (empty means disabled).
    
    # For HTTPS / Production -> https://my-domain.com
    # ports: [ "443:443" ]
    # environment:
    #   CADDY_GLOBAL_OPTIONS: ""
    #   SERVER_NAME: "my-domain.com"

    # If you need to bind all in one
    volumes:
      - ./:/app
    
    # If you need to bind only some folders
    # volumes:
    #   - ./local_path:/app/data       # where the data are stored for the {{ app.db }} service
    #   - ./local_path:/app/log        # the rotating log files
    #   - ./local_path:/app/pages      # the path used to define twig pages, bound to "@pages" scope
    #   - ./local_path:/app/public     # the public path for assets, bound to "@public" scope
    #   - ./local_path:/app/src        # where to write php "plugins"
    #   - ./local_path:/app/templates  # the template path for twig, bound to "@templates
```


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
| 10/12/2024 | 8.x, main |  ×  |     |     |     |     |     |
| 25/11/2024 | 7.x       |  ×  |     |     |     |     |     |
| 26/11/2023 | 6.x       |     |  ×  |     |     |     |     |
| 11/10/2023 | 5.x       |     |     |  ×  |     |     |     |
| 19/12/2022 | 4.x       |     |     |  ×  |     |     |     |
| 30/01/2022 | 3.x       |     |     |     |  ×  |     |     |
| 15/05/2021 | 2.x       |     |     |     |     |  ×  |     |
| 07/12/2019 | 1.x       |     |     |     |     |     |  ×  |
