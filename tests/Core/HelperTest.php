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

use Arnapou\SimpleSite\Core\Helper;
use Arnapou\SimpleSite\SimpleSite;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    use ConfigTestTrait;

    public static function dataSnakeCase(): \Generator
    {
        yield ['', ''];
        yield ['snake_case', 'snake_case'];
        yield ['camelCase', 'camel_case'];
        yield [' Hello World !', 'hello_world'];
        yield ['foo , bar', 'foo_bar'];
        yield ['Wôrķšƥáçè ~~sèťtïñğš~~ 5€', 'workspace_settings_5eur'];
    }

    #[DataProvider('dataSnakeCase')]
    #[RunInSeparateProcess]
    public function testSnakeCase(string $string, string $expected): void
    {
        self::assertSame($expected, self::createHelper()->toSnakeCase($string));
    }

    public static function dataCamelCase(): \Generator
    {
        yield ['', ''];
        yield ['snake_case', 'snakeCase'];
        yield ['camelCase', 'camelCase'];
        yield [' Hello World !', 'helloWorld'];
        yield ['foo , bar', 'fooBar'];
        yield ['Wôrķšƥáçè ~~sèťtïñğš~~ 5€', 'workspaceSettings5eur'];
    }

    #[DataProvider('dataCamelCase')]
    #[RunInSeparateProcess]
    public function testCamelCase(string $string, string $expected): void
    {
        self::assertSame($expected, self::createHelper()->toCamelCase($string));
    }

    public static function dataSlugify(): \Generator
    {
        yield ['', ''];
        yield [' hello world ', 'hello-world'];
        yield ['foo , bar', 'foo-bar'];
        yield ['Wôrķšƥáçè ~~sèťtïñğš~~ 5€', 'workspace-settings-5eur'];
    }

    #[DataProvider('dataSlugify')]
    #[RunInSeparateProcess]
    public function testSlugify(string $string, string $expected): void
    {
        self::assertSame($expected, self::createHelper()->slugify($string));
    }

    public static function dataExtension(): \Generator
    {
        yield ['', ''];
        yield ['foo', ''];
        yield ['foo.bar', 'bar'];
        yield ['/path/foo.baz', 'baz'];
        yield ['my.page.html', 'html'];
        yield ['my.page.twig', 'twig'];
        yield ['my.page.html.twig', 'twig'];
    }

    #[DataProvider('dataExtension')]
    #[RunInSeparateProcess]
    public function testExtension(string $string, string $expected): void
    {
        self::assertSame($expected, self::createHelper()->fileExtension($string));
    }

    public static function dataMinifyHtml(): \Generator
    {
        yield 'Basic minification' => [
            <<<HTML
                <h1>test</h1>
                <!-- comment -->
                <p>
                    <b>Hello </b>
                    <span>World !</span>
                </p>
                <?xml version="1.0" encoding="utf-8"?>
                HTML,
            <<<EXPECTED
                <h1>test</h1><p><b>Hello </b><span>World !</span></p>
                EXPECTED,
        ];

        yield 'Keep SCRIPT content' => [
            <<<HTML
                <p>foo</p>
                <script rel="foo">
                    var x = 123; 
                    /*
                     Hello
                     */
                </script>
                <p>bar</p>
                HTML,
            <<<EXPECTED
                <p>foo</p><script rel="foo">
                    var x = 123; 
                    /*
                     Hello
                     */
                </script><p>bar</p>
                EXPECTED,
        ];

        yield 'Keep PRE content' => [
            <<<HTML
                <p>foo</p>
                <pre class="foo">
                    some
                    text
                </pre>
                <p>bar</p>
                HTML,
            <<<EXPECTED
                <p>foo</p><pre class="foo">
                    some
                    text
                </pre><p>bar</p>
                EXPECTED,
        ];

        yield 'Keep TEXTAREA content' => [
            <<<HTML
                <p>foo</p>
                <textarea class="foo">
                    some
                    text
                </textarea>
                <p>bar</p>
                HTML,
            <<<EXPECTED
                <p>foo</p><textarea class="foo">
                    some
                    text
                </textarea><p>bar</p>
                EXPECTED,
        ];
    }

    #[DataProvider('dataMinifyHtml')]
    #[RunInSeparateProcess]
    public function testMinifyHtml(string $html, string $expected): void
    {
        self::assertSame($expected, self::createHelper()->minifyHtml($html));
    }

    #[RunInSeparateProcess]
    public function testThumbnail(): void
    {
        self::assertSame('/', self::createHelper()->thumbnail('', 20));
        self::assertSame('/', self::createHelper()->thumbnail('/', 20));

        // no base url
        self::assertSame('/foo/bar', self::createHelper()->thumbnail('foo/bar', 20));
        self::assertSame('/file.20.jpg', self::createHelper()->thumbnail('file.jpg', 20));

        // with base url
        self::assertSame('/zzz/foo/bar', self::createHelper('zzz')->thumbnail('foo/bar', 20));
        self::assertSame('/zzz/foo/bar', self::createHelper('zzz/')->thumbnail('foo/bar', 20));
        self::assertSame('/zzz/file.20.jpg', self::createHelper('zzz/')->thumbnail('file.jpg', 20));
    }

    #[RunInSeparateProcess]
    public function testAsset(): void
    {
        self::assertSame('/', self::createHelper()->asset(''));
        self::assertSame('/', self::createHelper()->asset('/'));

        // no base url
        self::assertSame('/foo/bar', self::createHelper()->asset('foo/bar'));

        // with base url
        self::assertSame('/zzz/foo/bar', self::createHelper('zzz/')->asset('foo/bar'));
    }

    #[RunInSeparateProcess]
    public function testPath(): void
    {
        // no base url
        self::assertSame('/favicon.ico', self::createHelper()->path('favicon'));
        self::assertSame('/robots.txt', self::createHelper()->path('robots_txt'));
        self::assertSame('/test.20.JpG', self::createHelper()->path('images', ['path' => 'test', 'size' => 20, 'ext' => 'JpG']));

        // with base url
        self::assertSame('/zzz/favicon.ico', self::createHelper('zzz/')->path('favicon'));
        self::assertSame('/zzz/robots.txt', self::createHelper('zzz/')->path('robots_txt'));
        self::assertSame('/zzz/test.20.JpG', self::createHelper('zzz/')->path('images', ['path' => 'test', 'size' => 20, 'ext' => 'JpG']));
    }

    #[RunInSeparateProcess]
    public function testPathPage(): void
    {
        // no base url
        self::assertSame('/hello/world', self::createHelper()->pathPage('hello/world'));
        self::assertSame('/hello/world/', self::createHelper()->pathPage('hello/world/'));

        // with base url
        self::assertSame('/zzz/hello/world', self::createHelper('zzz/')->pathPage('hello/world'));
        self::assertSame('/zzz/hello/world/', self::createHelper('zzz/')->pathPage('hello/world/'));
    }

    #[RunInSeparateProcess]
    public function testPathDir(): void
    {
        // no base url
        self::assertSame('/hello/world/', self::createHelper()->pathDir('hello/world'));
        self::assertSame('/hello/world//', self::createHelper()->pathDir('hello/world/'));

        // with base url
        self::assertSame('/zzz/hello/world/', self::createHelper('zzz/')->pathDir('hello/world'));
        self::assertSame('/zzz/hello/world//', self::createHelper('zzz/')->pathDir('hello/world/'));
    }

    private static function createHelper(string $baseUrl = '/'): Helper
    {
        self::resetContainer();

        $config = self::createConfigDemo($baseUrl);
        $request = new ServerRequest('GET', $config->base_path_root);

        SimpleSite::handle($config, $request);

        return SimpleSite::helper();
    }
}
