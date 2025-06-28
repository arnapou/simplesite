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

use Arnapou\SimpleSite\Core\Cache;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    use ConfigTestTrait;

    public function testFrom(): void
    {
        $cache = new Cache(self::createConfigDemo());

        $count = 0;
        $factory = function () use (&$count) {
            ++$count;

            return 'foo';
        };

        $key = uniqid('TEST', true);

        self::assertSame('foo', $cache->from($key, $factory, 1));
        self::assertSame(1, $count);

        self::assertSame('foo', $cache->from($key, $factory, 1));
        self::assertSame(1, $count); // @phpstan-ignore staticMethod.alreadyNarrowedType

        usleep(1_100_000);

        self::assertSame('foo', $cache->from($key, $factory, 1));
        self::assertSame(2, $count); // @phpstan-ignore staticMethod.impossibleType
    }

    public function testScenario(): void
    {
        $cache = new Cache(self::createConfigDemo());
        $key = uniqid('TEST', true);

        self::assertFalse($cache->has($key));
        self::assertTrue($cache->set($key, 123));

        self::assertTrue($cache->has($key));
        self::assertSame(123, $cache->get($key));

        self::assertTrue($cache->clear());
        self::assertFalse($cache->has($key));

        self::assertTrue($cache->set($key, 123));
        self::assertTrue($cache->has($key));
        self::assertTrue($cache->delete($key));
        self::assertFalse($cache->has($key));
    }
}
