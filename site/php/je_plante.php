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

use Arnapou\SimpleSite\Core\Controller;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute(
            'je_plante',
            function () {
                throw new \RuntimeException('Plantage volontaire');
            },
            'je_plante'
        );
    }
};
