#!/bin/bash
set -e

#
# This file is used as a docker entrypoint to init SimpleSite.
#

# Auto-create folders if they don't exist.

mkdir -p /app/data
mkdir -p /app/log
mkdir -p /app/pages
mkdir -p /app/public
mkdir -p /app/src
mkdir -p /app/templates

chown www-data:www-data /app/*

# Write the php index.php script.

cat <<EOF > /app/public/index.php
<?php
// ╔═══════════════════════════════════════════════╗
// ║ Do NOT modify this file.                      ║
// ║ It is automatically overridden at each start. ║
// ╚═══════════════════════════════════════════════╝
declare(strict_types=1);
require '/srv/simplesite.phar';
SimpleSite::run(
    base_path_admin: getenv('SIMPLESITE_ADMIN') ?: '',
    path_data:       '/app/data',
    log_path:        '/app/log',
    path_pages:      '/app/pages',
    path_public:     '/app/public',
    path_php:        '/app/src',
    path_templates:  '/app/templates',
    path_cache:      '/cache/simplesite',
);
EOF

chmod 644 /app/public/index.php

# Set up a default Home page if it does not exists
if [[ ! -f /app/pages/index.twig ]]; then
cat <<EOF > /app/pages/index.twig
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>SimpleSite</title>
  <style>{{ source('@internal/simple.css') }}</style>
</head>
<body>
<header>
  <nav>
    <a href="{{ path_dir('') }}">Home</a>
    <a href="{{ path_dir('todo') }}">TODO</a>
  </nav>
  <h1>Demo page</h1>
  <p>This is the file <code>@pages/index.twig</code>.</p></header>
<main>
  <p>
    For help, please take a look at <a href="https://simplesite.arnapou.net">simplesite.arnapou.net</a>,
    or the <a href="https://gitlab.com/arnapou/project/simplesite">GitLab project</a>.
  </p>
  <p>Enjoy and have fun 🙂</p>
</main>
</body>
</html>
EOF

chown www-data:www-data /app/pages/index.twig
fi

# Start FrankenPHP.

exec frankenphp run --config /etc/caddy/Caddyfile
