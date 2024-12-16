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
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class SimpleSiteTest extends TestCase
{
    use ConfigTestTrait;

    public const array SERVER = [
        'REMOTE_ADDR' => '1.2.3.4',
        'REMOTE_HOST' => '1.2.3.4',
        'PHP_SELF' => '/index.php',
    ];
    private Config $config;

    protected function setUp(): void
    {
        $this->config = self::createConfigDemo();
    }

    #[RunInSeparateProcess]
    public function testRobotTxt(): void
    {
        $response = SimpleSite::handle($this->config, self::createServerRequest('GET', '/robots.txt'));
        $body = $response->getBody()->getContents();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('User-agent', $body);
        self::assertStringContainsString('Disallow:', $body);
    }

    public static function dataImages(): \Generator
    {
        yield [self::createServerRequest('GET', '/favicon.ico')];
        yield [self::createServerRequest('GET', '/php.100.png')];
        yield [self::createServerRequest('GET', '/assets/twig.200.png')];
        yield [self::createServerRequest('GET', '/assets/twig.400.png')];

        yield [self::createServerRequest('GET', '/admin/lock.svg')];
        yield [self::createServerRequest('GET', '/admin/user.svg')];
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

    public static function dataPageHtml(): \Generator
    {
        yield [self::createServerRequest('GET', '/'), '<h2>Mindset</h2>'];
        yield [self::createServerRequest('GET', '/index'), '<h2>Mindset</h2>'];

        yield [self::createServerRequest('GET', '/menu/database'), '<h1>Database</h1>'];
        yield [self::createServerRequest('GET', '/menu/errors'), '<h1>Error pages</h1>'];
        yield [self::createServerRequest('GET', '/menu/images'), '<h1>Images</h1>'];
        yield [self::createServerRequest('GET', '/menu/pages'), '<h1>Pages</h1>'];
        yield [self::createServerRequest('GET', '/menu/php'), '<h1>Php</h1>'];
        yield [self::createServerRequest('GET', '/menu/templating'), '<h1>Twig Templating</h1>'];

        yield [self::createServerRequest('GET', '/hello-world'), '<h1>Hello world !</h1>'];

        yield [self::createServerRequest('GET', '/admin/login'), '<legend>Login</legend>'];
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

    public static function dataPageXml(): \Generator
    {
        yield [
            self::createServerRequest('GET', '/sitemap.xml'),
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">',
            '</lastmod><loc>http://localhost/menu/database</loc></url>',
            '</lastmod><loc>http://localhost/menu/errors</loc></url>',
            '</lastmod><loc>http://localhost/menu/images</loc></url>',
            '</lastmod><loc>http://localhost/menu/pages</loc></url>',
            '</lastmod><loc>http://localhost/menu/php</loc></url>',
            '</lastmod><loc>http://localhost/menu/templating</loc></url>',
            '</lastmod><loc>http://localhost/</loc></url>',
            '</urlset>',
        ];
    }

    #[RunInSeparateProcess]
    #[DataProvider('dataPageXml')]
    public function testPageXml(ServerRequestInterface $request, string ...$strings): void
    {
        $response = SimpleSite::handle($this->config, $request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('text/xml', $response->getHeader('Content-Type')[0] ?? '');

        $contents = $response->getBody()->getContents();
        foreach ($strings as $string) {
            self::assertStringContainsString($string, $contents);
        }
    }

    public static function dataRedirect(): \Generator
    {
        yield [self::createServerRequest('GET', '/menu'), '/menu/'];

        yield [self::createServerRequest('GET', '/foo/bar.html'), '/foo/bar'];
        yield [self::createServerRequest('GET', '/foo/bar.twig'), '/foo/bar'];
        yield [self::createServerRequest('GET', '/foo/bar.html.twig'), '/foo/bar.html'];

        yield [self::createServerRequest('GET', '/admin'), '/admin/'];
        yield [self::createServerRequest('GET', '/admin/'), '/admin/login'];
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

    public static function dataNotFound(): \Generator
    {
        yield ['GET', '/assets/twig.abc.png'];
        yield ['GET', '/assets/twig.15.png'];
        yield ['GET', '/assets/twig.2001.png'];
        yield ['GET', '/assets/unknown.200.png'];

        yield ['GET', '/admin/init'];

        yield ['GET', '/foo/bar'];
        yield ['GET', '/je_n_existe_pas'];
    }

    #[RunInSeparateProcess]
    #[DataProvider('dataNotFound')]
    public function testNotFound(string $method, string $uri): void
    {
        $request = self::createServerRequest($method, $uri);
        $response = SimpleSite::handle($this->config, $request);

        self::assertSame(404, $response->getStatusCode());
    }

    public static function dataInternalError(): \Generator
    {
        yield [self::createServerRequest('GET', '/crashed')];
        yield [self::createServerRequest('GET', '/hello-world?killme=1'), 'Arrrgghh .... I am killed ...'];
    }

    #[RunInSeparateProcess]
    #[DataProvider('dataInternalError')]
    public function testInternalError(ServerRequestInterface $request, string $string = ''): void
    {
        $response = SimpleSite::handle($this->config, $request);

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString($string, $response->getBody()->getContents());
    }

    private static function createServerRequest(string $method, string $uri): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, body: '', serverParams: self::SERVER);
    }
}
