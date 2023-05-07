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

namespace Arnapou\SimpleSite\Exception;

class ServiceUnknown extends SimplesiteProblem
{
    public function __construct(string $name = '')
    {
        parent::__construct("The service '$name' is unknown.");
    }
}