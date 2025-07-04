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

namespace Arnapou\SimpleSite;

interface YamlContextLoader
{
    /**
     * @param array<mixed> $context
     *
     * @return array<mixed>
     */
    public function __invoke(string $view, array $context): array;
}
