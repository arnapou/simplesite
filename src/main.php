<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(\E_ALL & ~\E_USER_DEPRECATED);

class_alias(SimpleSite::class, \SimpleSite::class);
class_alias(SimpleSite::class, \Arnapou\SimpleSite::class);

// Fake core scope.
class_alias(SimpleSite::class, Core\SimpleSite::class);
class_alias(Controller::class, Core\Controller::class);
class_alias(PhpCode::class, Core\PhpCode::class);
