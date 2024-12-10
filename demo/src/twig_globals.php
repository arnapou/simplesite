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
        $twig = SimpleSite::twigEnvironment();
        $parameters = SimpleSite::database()->getTable('twig_globals');

        foreach ($parameters as $key => $data) {
            $twig->addGlobal($key, $data['value'] ?? '');
        }
        $twig->addGlobal('simplesite_phar_size', filesize(__DIR__.'/../../bin/simplesite.phar'));

        $twig->addFilter(
            new \Twig\TwigFilter(
                'preg_replace',
                static fn(string $source, string $pattern, string $replace) => preg_replace($pattern, $replace, $source)
            )
        );
    }
};
