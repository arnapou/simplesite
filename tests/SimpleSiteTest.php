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

namespace Arnapou\SimpleSite\Tests;

use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\SimpleSite;
use Generator;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class SimpleSiteTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config(
            'test',
            path_public: __DIR__ . '/../site/public',
            path_cache: __DIR__ . '/../site/cache',
            path_data: __DIR__ . '/../site/data',
            path_templates: __DIR__ . '/../site/templates',
            path_php: __DIR__ . '/../site/php',
        );
    }

    #[RunInSeparateProcess]
    public function testRobotTxt(): void
    {
        $response = SimpleSite::handle($this->config, new ServerRequest('GET', '/robots.txt'));
        $body = $response->getBody()->getContents();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('User-agent', $body);
        self::assertStringContainsString('Disallow:', $body);
    }

    public static function dataImages(): Generator
    {
        yield [new ServerRequest('GET', '/favicon.ico')];
        yield [new ServerRequest('GET', '/assets/twig.200.png')];
        yield [new ServerRequest('GET', '/assets/twig.400.png')];
    }

    #[RunInSeparateProcess]
    #[DataProvider('dataImages')]
    public function testImages(ServerRequestInterface $request): void
    {
        $response = SimpleSite::handle($this->config, $request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('image/', $response->getHeader('Content-Type')[0] ?? '');
        self::assertNotEmpty($response->getHeader('ETag')[0] ?? null);
        self::assertNotEmpty($response->getHeader('Cache-Control')[0] ?? null);
    }

    public static function dataPageHtml(): Generator
    {
        yield [new ServerRequest('GET', '/'), "<h2>Etat d'esprit</h2>"];
        yield [new ServerRequest('GET', '/index'), "<h2>Etat d'esprit</h2>"];

        yield [new ServerRequest('GET', '/pages/datas'), '<h1>Datas</h1>'];
        yield [new ServerRequest('GET', '/pages/error_pages'), "<h1>Pages d'erreur</h1>"];
        yield [new ServerRequest('GET', '/pages/images'), '<h1>Images</h1>'];
        yield [new ServerRequest('GET', '/pages/logs'), '<h1>Logs</h1>'];
        yield [new ServerRequest('GET', '/pages/php'), '<h1>Php</h1>'];
        yield [new ServerRequest('GET', '/pages/templating'), '<h1>Templating Twig</h1>'];

        yield [new ServerRequest('GET', '/hello-world'), '<h1>Hello world !</h1>'];
    }

    #[RunInSeparateProcess]
    #[DataProvider('dataPageHtml')]
    public function testPageHtml(ServerRequestInterface $request, string $string): void
    {
        $response = SimpleSite::handle($this->config, $request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('text/html', $response->getHeader('Content-Type')[0] ?? '');
        self::assertStringContainsString($string, $response->getBody()->getContents());
    }

    public static function dataRedirect(): Generator
    {
        yield [new ServerRequest('GET', '/assets'), '/assets/'];

        yield [new ServerRequest('GET', '/foo/bar.php'), '/foo/bar'];
        yield [new ServerRequest('GET', '/foo/bar.html'), '/foo/bar'];
        yield [new ServerRequest('GET', '/foo/bar.htm'), '/foo/bar'];
        yield [new ServerRequest('GET', '/foo/bar.twig'), '/foo/bar'];
        yield [new ServerRequest('GET', '/foo/bar.tpl'), '/foo/bar'];
        yield [new ServerRequest('GET', '/foo/bar.html.twig'), '/foo/bar'];
    }

    #[RunInSeparateProcess]
    #[DataProvider('dataRedirect')]
    public function testRedirect(ServerRequestInterface $request, string $url): void
    {
        $response = SimpleSite::handle($this->config, $request);

        self::assertContains($response->getStatusCode(), [301, 302]);
        self::assertStringStartsWith('text/html', $response->getHeader('Content-Type')[0] ?? '');
        self::assertSame($url, $response->getHeader('Location')[0] ?? '');
    }

    public static function dataNotFound(): Generator
    {
        yield [new ServerRequest('GET', '/assets/twig.abc.png')];
        yield [new ServerRequest('GET', '/assets/twig.15.png')];
        yield [new ServerRequest('GET', '/assets/twig.1501.png')];
        yield [new ServerRequest('GET', '/assets/unknown.200.png')];

        yield [new ServerRequest('GET', '/foo/bar')];
        yield [new ServerRequest('GET', '/not-found/')];
    }

    #[RunInSeparateProcess]
    #[DataProvider('dataNotFound')]
    public function testNotFound(ServerRequestInterface $request): void
    {
        $response = SimpleSite::handle($this->config, $request);

        self::assertSame(404, $response->getStatusCode());
    }

    public static function dataInternalError(): Generator
    {
        yield [new ServerRequest('GET', '/je_plante')];
        yield [new ServerRequest('GET', '/hello-world?killme=1'), 'Arrrgghh .... I am killed ...'];
    }

    #[RunInSeparateProcess]
    #[DataProvider('dataInternalError')]
    public function testInternalError(ServerRequestInterface $request, string $string = ''): void
    {
        $response = SimpleSite::handle($this->config, $request);

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString($string, $response->getBody()->getContents());
    }
}
