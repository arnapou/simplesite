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

use Arnapou\SimpleSite\SimpleSite;
use Arnapou\SimpleSite\PhpCode;

return new class() implements PhpCode {
    public function init(): void
    {
        $twigEnvironment = SimpleSite::twigEnvironment();
        $database = SimpleSite::database();

        $parameters = $database->getTable('twig_globals');
        foreach ($parameters as $key => $data) {
            $twigEnvironment->addGlobal($key, $data['value'] ?? '');
        }
    }
};
