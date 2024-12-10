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

use Arnapou\SimpleSite\Core\TwigExtension;
use Arnapou\SimpleSite\SimpleSite;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use stdClass;

class TwigExtensionTest extends TestCase
{
    use ConfigTestTrait;

    #[RunInSeparateProcess]
    public function testChunk(): void
    {
        self::assertSame(
            [[1, 2, 3], [4, 5, 6], [7, 8, 9], [10]],
            self::createExtension()->chunk(range(1, 10), 3),
        );
        self::assertSame(
            [[1, 2, 3, 4, 5], [6, 7, 8, 9, 10]],
            self::createExtension()->chunk(range(1, 10), 5),
        );
    }

    #[RunInSeparateProcess]
    public function testRepeat(): void
    {
        self::assertSame('foo foo foo ', self::createExtension()->repeat('foo ', 3));
    }

    #[RunInSeparateProcess]
    public function testGetclass(): void
    {
        self::assertSame('string', self::createExtension()->getclass('foo'));
        self::assertSame('stdClass', self::createExtension()->getclass(new stdClass()));
    }

    #[RunInSeparateProcess]
    public function testThumbnail(): void
    {
        self::assertSame('/', self::createExtension()->thumbnail('', 20));
        self::assertSame('/', self::createExtension()->thumbnail('/', 20));

        // no base url
        self::assertSame('/foo/bar', self::createExtension()->thumbnail('foo/bar', 20));
        self::assertSame('/file.20.jpg', self::createExtension()->thumbnail('file.jpg', 20));

        // with base url
        self::assertSame('/zzz/foo/bar', self::createExtension('zzz')->thumbnail('foo/bar', 20));
        self::assertSame('/zzz/foo/bar', self::createExtension('zzz/')->thumbnail('foo/bar', 20));
        self::assertSame('/zzz/file.20.jpg', self::createExtension('zzz/')->thumbnail('file.jpg', 20));
    }

    #[RunInSeparateProcess]
    public function testAsset(): void
    {
        self::assertSame('/', self::createExtension()->asset(''));
        self::assertSame('/', self::createExtension()->asset('/'));

        // no base url
        self::assertSame('/foo/bar', self::createExtension()->asset('foo/bar'));

        // with base url
        self::assertSame('/zzz/foo/bar', self::createExtension('zzz/')->asset('foo/bar'));
    }

    #[RunInSeparateProcess]
    public function testPath(): void
    {
        // no base url
        self::assertSame('/favicon.ico', self::createExtension()->path('favicon'));
        self::assertSame('/robots.txt', self::createExtension()->path('robots_txt'));
        self::assertSame('/test.20.JpG', self::createExtension()->path('images', ['path' => 'test', 'size' => 20, 'ext' => 'JpG']));

        // with base url
        self::assertSame('/zzz/favicon.ico', self::createExtension('zzz/')->path('favicon'));
        self::assertSame('/zzz/robots.txt', self::createExtension('zzz/')->path('robots_txt'));
        self::assertSame('/zzz/test.20.JpG', self::createExtension('zzz/')->path('images', ['path' => 'test', 'size' => 20, 'ext' => 'JpG']));
    }

    #[RunInSeparateProcess]
    public function testPathPage(): void
    {
        // no base url
        self::assertSame('/hello/world', self::createExtension()->path_page('hello/world'));
        self::assertSame('/hello/world/', self::createExtension()->path_page('hello/world/'));

        // with base url
        self::assertSame('/zzz/hello/world', self::createExtension('zzz/')->path_page('hello/world'));
        self::assertSame('/zzz/hello/world/', self::createExtension('zzz/')->path_page('hello/world/'));
    }

    #[RunInSeparateProcess]
    public function testPathDir(): void
    {
        // no base url
        self::assertSame('/hello/world/', self::createExtension()->path_dir('hello/world'));
        self::assertSame('/hello/world//', self::createExtension()->path_dir('hello/world/'));

        // with base url
        self::assertSame('/zzz/hello/world/', self::createExtension('zzz/')->path_dir('hello/world'));
        self::assertSame('/zzz/hello/world//', self::createExtension('zzz/')->path_dir('hello/world/'));
    }

    private static function createExtension(string $baseUrl = '/'): TwigExtension
    {
        $config = self::createConfigSite($baseUrl);

        $container = self::resetContainer();
        SimpleSite::handle($config, new ServerRequest('GET', $config->base_path_root));

        return $container->get(TwigExtension::class);
    }
}
