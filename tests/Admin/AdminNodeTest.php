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

namespace Arnapou\SimpleSite\Tests\Admin;

use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;
use Arnapou\SimpleSite\Admin\AdminNode;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Core\View;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\TestCase;

class AdminNodeTest extends TestCase
{
    use ConfigTestTrait;

    private Config $config;

    protected function setUp(): void
    {
        $container = self::resetContainer();
        $container->registerInstance(Config::class, $this->config = self::createConfigDemo());
    }

    public function testInstanceRoot(): void
    {
        $node = new AdminNode('');
        self::assertNull($node->view);
        self::assertTrue($node->dir);
        self::assertSame('', $node->path);
        self::assertSame('', $node->ext);
        self::assertSame('', (string) $node);
    }

    public function testInstanceScopeRoot(): void
    {
        $node = new AdminNode(new View('@pages/'));
        self::assertSame('@pages', $node->view?->name);
        self::assertTrue($node->dir);
        self::assertSame($this->config->path_pages, $node->path);
        self::assertSame('', $node->ext);
        self::assertSame('@pages', (string) $node);
    }

    public function testInstanceScopeFolder(): void
    {
        $node = new AdminNode('@public/assets/');
        self::assertSame('@public/assets', $node->view?->name);
        self::assertTrue($node->dir);
        self::assertSame($this->config->path_public . '/assets', $node->path);
        self::assertSame('', $node->ext);
        self::assertSame('@public/assets', (string) $node);
    }

    public function testInstanceScopeFile(): void
    {
        $node = new AdminNode('@public/assets/favicon.svg');
        self::assertSame('@public/assets/favicon.svg', $node->view?->name);
        self::assertFalse($node->dir);
        self::assertSame($this->config->path_public . '/assets/favicon.svg', $node->path);
        self::assertSame('svg', $node->ext);
        self::assertSame('@public/assets/favicon.svg', (string) $node);

        self::assertTrue($node->canDelete(), 'canDelete');
        self::assertFalse($node->canEdit(), 'canEdit');
        self::assertTrue($node->canRename(), 'canRename');
        self::assertFalse($node->canCreate(), 'canCreate');

        self::assertFalse($node->isForbidden(), 'isForbidden');
        self::assertTrue($node->isPicture(), 'isPicture');
        self::assertFalse($node->isSound(), 'isSound');
        self::assertFalse($node->isText(), 'isText');
        self::assertFalse($node->isVideo(), 'isVideo');

        self::assertTrue($node->exists(), 'exists');
        self::assertSame('favicon.svg', $node->name(), 'name');
        self::assertSame('/assets/favicon.svg', $node->publicUrl(), 'publicUrl');
        self::assertSame('5.1 KB', $node->size(), 'size');
        self::assertSame('file-picture', $node->symbol(), 'symbol');
        self::assertMatchesRegularExpression('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d$/', $node->time(), 'time');
    }

    public function testFailurePathTraversable(): void
    {
        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Unauthorized access outside root paths.');
        $this->expectExceptionCode(StatusClientError::BadRequest->value);
        new AdminNode('@public/path/../../truc');
    }
}
