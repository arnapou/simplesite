<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <me@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Core;

use Arnapou\PFDB\Exception\DirectoryNotFoundException;
use Arnapou\PFDB\Exception\InvalidTableNameException;
use Arnapou\PFDB\Storage\CachedFileStorage;
use Arnapou\PFDB\Storage\YamlFileStorage;
use Arnapou\Psr\Psr7HttpMessage\Status\StatusServerError;

final class DbStorage extends CachedFileStorage
{
    public function __construct(Config $config)
    {
        try {
            parent::__construct(
                new YamlFileStorage($config->path_data ?? throw Problem::emptyVariable('path_data')),
                $config->pathCache('data'),
            );
        } catch (DirectoryNotFoundException|InvalidTableNameException $e) {
            throw new Problem($e->getMessage(), StatusServerError::InternalServerError, $e);
        }
    }
}
