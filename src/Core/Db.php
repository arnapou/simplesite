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
use Arnapou\PFDB\Storage\StorageInterface;

final class Db extends AbstractDatabase
{
    public function __construct(StorageInterface $storage)
    {
        parent::__construct($storage);
    }
}
