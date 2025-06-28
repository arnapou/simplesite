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

use Arnapou\PFDB\Core\AbstractDatabase;

/**
 * This is the same as {@see Db} but with a readonly storage to avoid any
 * internal change of data from public twig files.
 */
final class DbPublic extends AbstractDatabase
{
    public function __construct(DbStoragePublic $storage)
    {
        parent::__construct($storage);
    }
}
