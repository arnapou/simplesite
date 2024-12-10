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

namespace Arnapou\SimpleSite\Tests\Core;

use Arnapou\SimpleSite\Core\Pages;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\TestCase;

class PagesTest extends TestCase
{
    use ConfigTestTrait;

    public function testList(): void
    {
        $pages = new Pages(self::createConfigSite());

        $list = iterator_to_array($pages->list());
        $urls = array_keys($list);
        sort($urls);

        self::assertSame(
            [
                '',
                'menu/datas',
                'menu/error_pages',
                'menu/images',
                'menu/logs',
                'menu/php',
                'menu/templating',
            ],
            $urls,
        );
    }
}
