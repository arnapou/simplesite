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

namespace Arnapou\SimpleSite\Tests\Core;

use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class SitemapTest extends TestCase
{
    use ConfigTestTrait;

    protected function setUp(): void
    {
        $container = self::resetContainer();
        $container->registerInstance(Config::class, self::createConfigDemo());
    }

    #[RunInSeparateProcess]
    public function testList(): void
    {
        $list = iterator_to_array($this->getSitemap()->files());
        $urls = array_keys($list);
        sort($urls);

        self::assertSame(
            [
                '',
                'menu/database',
                'menu/errors',
                'menu/images',
                'menu/pages',
                'menu/php',
                'menu/templating',
            ],
            $urls,
        );
    }
}
