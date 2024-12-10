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
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public static function dataSnakeCase(): Generator
    {
        yield ['', ''];
        yield ['snake_case', 'snake_case'];
        yield ['camelCase', 'camel_case'];
        yield [' Hello World !', 'hello_world'];
        yield ['foo , bar', 'foo_bar'];
        yield ['Wôrķšƥáçè ~~sèťtïñğš~~ 5€', 'workspace_settings_5eur'];
    }

    #[DataProvider('dataSnakeCase')]
    public function testSnakeCase(string $string, string $expected): void
    {
        self::assertSame($expected, new Helper()->toSnakeCase($string));
    }

    public static function dataCamelCase(): Generator
    {
        yield ['', ''];
        yield ['snake_case', 'snakeCase'];
        yield ['camelCase', 'camelCase'];
        yield [' Hello World !', 'helloWorld'];
        yield ['foo , bar', 'fooBar'];
        yield ['Wôrķšƥáçè ~~sèťtïñğš~~ 5€', 'workspaceSettings5eur'];
    }

    #[DataProvider('dataCamelCase')]
    public function testCamelCase(string $string, string $expected): void
    {
        self::assertSame($expected, new Helper()->toCamelCase($string));
    }

    public static function dataSlugify(): Generator
    {
        yield ['', ''];
        yield [' hello world ', 'hello-world'];
        yield ['foo , bar', 'foo-bar'];
        yield ['Wôrķšƥáçè ~~sèťtïñğš~~ 5€', 'workspace-settings-5eur'];
    }

    #[DataProvider('dataSlugify')]
    public function testSlugify(string $string, string $expected): void
    {
        self::assertSame($expected, new Helper()->slugify($string));
    }

    public static function dataExtension(): Generator
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
    public function testExtension(string $string, string $expected): void
    {
        self::assertSame($expected, new Helper()->fileExtension($string));
    }

    public static function dataMinifyHtml(): Generator
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
    public function testMinifyHtml(string $html, string $expected): void
    {
        self::assertSame($expected, new Helper()->minifyHtml($html));
    }
}
