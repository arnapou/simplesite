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

use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Core\Scope;
use Arnapou\SimpleSite\Core\View;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    use ConfigTestTrait;

    private Config $config;

    protected function setUp(): void
    {
        $container = self::resetContainer();
        $container->registerInstance(Config::class, $this->config = self::createConfigDemo());
    }

    #[RunInSeparateProcess]
    public function testInstanceEmpty(): void
    {
        $view = new View('');
        self::assertSame(Scope::default()->toString(), $view->scope, 'scope');
        self::assertSame('/', $view->path, 'path');
        self::assertSame("{$this->config->path_pages}", $view->real, 'real');
        self::assertSame("{$this->config->path_pages}", $view->base, 'base');
        self::assertSame('@pages', $view->name, 'name');
        self::assertTrue($view->isDir, 'isDir');
        self::assertFalse($view->isFile, 'isFile');
        self::assertTrue($view->exists, 'exists');
        self::assertSame('@pages', (string) $view, '__toString');
    }

    public function testInstanceSlash(): void
    {
        $view = new View('/');
        self::assertSame(Scope::default()->toString(), $view->scope, 'scope');
        self::assertSame('/', $view->path, 'path');
        self::assertSame("{$this->config->path_pages}", $view->real, 'real');
        self::assertSame("{$this->config->path_pages}", $view->base, 'base');
        self::assertSame('@pages', $view->name, 'name');
        self::assertTrue($view->isDir, 'isDir');
        self::assertFalse($view->isFile, 'isFile');
        self::assertTrue($view->exists, 'exists');
        self::assertSame('@pages', (string) $view, '__toString');
    }

    #[RunInSeparateProcess]
    public function testInstanceScopeEmpty(): void
    {
        $view = new View('@pages');
        self::assertSame(Scope::pages->toString(), $view->scope, 'scope');
        self::assertSame('/', $view->path, 'path');
        self::assertSame("{$this->config->path_pages}", $view->real, 'real');
        self::assertSame("{$this->config->path_pages}", $view->base, 'base');
        self::assertSame('@pages', $view->name, 'name');
        self::assertTrue($view->isDir, 'isDir');
        self::assertFalse($view->isFile, 'isFile');
        self::assertTrue($view->exists, 'exists');
        self::assertSame('@pages', (string) $view, '__toString');
    }

    public function testInstanceScopeSlash(): void
    {
        $view = new View('@templates/');
        self::assertSame(Scope::templates->toString(), $view->scope, 'scope');
        self::assertSame('/', $view->path, 'path');
        self::assertSame("{$this->config->path_templates}", $view->real, 'real');
        self::assertSame("{$this->config->path_templates}", $view->base, 'base');
        self::assertSame('@templates', $view->name, 'name');
        self::assertTrue($view->isDir, 'isDir');
        self::assertFalse($view->isFile, 'isFile');
        self::assertTrue($view->exists, 'exists');
        self::assertSame('@templates', (string) $view, '__toString');
    }

    public function testInstanceExistentFolder(): void
    {
        $view = new View('@public/assets/');
        self::assertSame(Scope::public->toString(), $view->scope, 'scope');
        self::assertSame('/assets', $view->path, 'path');
        self::assertSame("{$this->config->path_public}/assets", $view->real, 'real');
        self::assertSame("{$this->config->path_public}", $view->base, 'base');
        self::assertSame('@public/assets', $view->name, 'name');
        self::assertTrue($view->isDir, 'isDir');
        self::assertFalse($view->isFile, 'isFile');
        self::assertTrue($view->exists, 'exists');
        self::assertSame('@public/assets', (string) $view, '__toString');
    }

    public function testInstanceExistentFile(): void
    {
        $view = new View('@public/assets/favicon.svg');
        self::assertSame(Scope::public->toString(), $view->scope, 'scope');
        self::assertSame('/assets/favicon.svg', $view->path, 'path');
        self::assertSame("{$this->config->path_public}/assets/favicon.svg", $view->real, 'real');
        self::assertSame("{$this->config->path_public}", $view->base, 'base');
        self::assertSame('@public/assets/favicon.svg', $view->name, 'name');
        self::assertFalse($view->isDir, 'isDir');
        self::assertTrue($view->isFile, 'isFile');
        self::assertTrue($view->exists, 'exists');
        self::assertSame('@public/assets/favicon.svg', (string) $view, '__toString');
    }

    public function testInstanceNonExistentFolder(): void
    {
        $view = new View('@public/assets/test.svg');
        self::assertSame(Scope::public->toString(), $view->scope, 'scope');
        self::assertSame('/assets/test.svg', $view->path, 'path');
        self::assertSame("{$this->config->path_public}/assets/test.svg", $view->real, 'real');
        self::assertSame("{$this->config->path_public}", $view->base, 'base');
        self::assertSame('@public/assets/test.svg', $view->name, 'name');
        self::assertFalse($view->isDir, 'isDir');
        self::assertTrue($view->isFile, 'isFile');
        self::assertFalse($view->exists, 'exists');
        self::assertSame('@public/assets/test.svg', (string) $view, '__toString');
    }

    public function testInstanceNonExistentFile(): void
    {
        $view = new View('@public/assets/test/');
        self::assertSame(Scope::public->toString(), $view->scope, 'scope');
        self::assertSame('/assets/test', $view->path, 'path');
        self::assertSame("{$this->config->path_public}/assets/test", $view->real, 'real');
        self::assertSame("{$this->config->path_public}", $view->base, 'base');
        self::assertSame('@public/assets/test', $view->name, 'name');
        self::assertTrue($view->isDir, 'isDir');
        self::assertFalse($view->isFile, 'isFile');
        self::assertFalse($view->exists, 'exists');
        self::assertSame('@public/assets/test', (string) $view, '__toString');
    }

    public function testFailureBadScope1(): void
    {
        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Invalid scope "@foo"');
        $this->expectExceptionCode(StatusClientError::BadRequest->value);
        new View('@foo');
    }

    public function testFailureBadScope2(): void
    {
        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Invalid scope "@foo-bar"');
        $this->expectExceptionCode(StatusClientError::BadRequest->value);
        new View('@foo-bar/test');
    }

    public function testFailurePathTraversable(): void
    {
        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Unauthorized access outside root paths.');
        $this->expectExceptionCode(StatusClientError::BadRequest->value);
        new View('@public/path/../../truc');
    }

    public function testFailurePathTraversableRelative(): void
    {
        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Unauthorized access outside root paths.');
        $this->expectExceptionCode(StatusClientError::BadRequest->value);
        new View('@public')->relative('../../../truc');
    }

    public function testLimitsOfDirname(): void
    {
        $view = new View('@public/foo/../foo/.///bar/baz');

        self::assertSame("{$this->config->path_public}/foo/bar/baz", $view->real);
        self::assertSame("{$this->config->path_public}/foo/bar", $view->dirname()->real);
        self::assertSame("{$this->config->path_public}/foo", $view->dirname(2)->real);
        self::assertSame("{$this->config->path_public}", $view->dirname(3)->real);
        self::assertSame("{$this->config->path_public}", $view->dirname(4)->real);
        self::assertSame("{$this->config->path_public}", $view->dirname(5)->real);
        self::assertSame("{$this->config->path_public}", $view->root()->real);
    }

    public function testExtension(): void
    {
        self::assertSame('', new View('@public/foo')->extension());
        self::assertSame('', new View('@public/foo/')->extension());
        self::assertSame('ZiP', new View('@public/foo/bar.ZiP')->extension());
    }

    public function testBasename(): void
    {
        self::assertSame('foo', new View('@public/foo')->basename());
        self::assertSame('foo', new View('@public/foo/')->basename());
        self::assertSame('bar.ZiP', new View('@public/foo/bar.ZiP')->basename());
    }

    public static function dataList(): \Generator
    {
        yield [
            null,
            '@templates/demo/',
            [false, '/demo/hello.twig'],
            [false, '/demo/hello.yaml'],
        ];

        // default
        $dirs = [[true, '/menu']];
        $files = [[false, '/index.twig'], [false, '/php.png'], [false, '/test.json']];
        yield [null, '@pages', ...$dirs, ...$files];
        yield ['', '@pages', ...$dirs, ...$files];

        // only dirs
        yield [true, '@pages', ...$dirs];
        yield ['d', '@pages', ...$dirs];
        yield ['dir', '@pages', ...$dirs];

        // only files
        yield [false, '@pages', ...$files];
        yield ['f', '@pages', ...$files];
        yield ['files', '@pages', ...$files];

        // none because wrong string
        yield ['zzz', '@pages'];
    }

    /**
     * @param array<mixed> ...$expected
     */
    #[DataProvider('dataList')]
    public function testList(string|bool|null $type, string $name, array ...$expected): void
    {
        $view = new View($name);
        self::assertSame(
            $expected,
            array_map(
                static fn (View $view) => [$view->isDir, $view->path],
                $view->list($type),
            ),
        );
    }

    public function testRelative(): void
    {
        self::assertSame('@public/foo', new View('@public')->relative('foo')->name);
        self::assertSame('@public/bar', new View('@public/foo')->relative('bar')->name);
        self::assertSame('@public/foo/bar', new View('@public/foo/')->relative('bar')->name);
    }
}
