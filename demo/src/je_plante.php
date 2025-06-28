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

use Arnapou\SimpleSite\Controller;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute(
            'crashed',
            static fn () => throw new \RuntimeException('Intentional crash', 0, new \Exception('previous')),
            'crashed'
        );
    }
};
