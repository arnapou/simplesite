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

namespace Arnapou\SimpleSite\Tests\Admin;

use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;
use Arnapou\SimpleSite\Admin\AdminNode;
use Arnapou\SimpleSite\Admin\AdminScope;
use Arnapou\SimpleSite\Core\Problem;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\TestCase;

class AdminNodeTest extends TestCase
{
    use ConfigTestTrait;

    public function testInstanceRoot(): void
    {
        $config = self::createConfigSite();
        $node = AdminNode::from($config, '');
        self::assertSame('', $node->root);
        self::assertNull($node->scope);
        self::assertSame('', $node->path);
        self::assertSame('/', $node->rel);
        self::assertSame('', $node->ext);
        self::assertTrue($node->dir);

        self::assertSame('', (string) $node);
    }

    public function testInstanceScopeRoot(): void
    {
        $config = self::createConfigSite();
        $node = AdminNode::from($config, '@pages/');
        self::assertSame($config->path_pages, $node->root);
        self::assertSame(AdminScope::pages, $node->scope);
        self::assertSame($config->path_pages, $node->path);
        self::assertSame('/', $node->rel);
        self::assertSame('', $node->ext);
        self::assertTrue($node->dir);
    }

    public function testInstanceScopeFolder(): void
    {
        $config = self::createConfigSite();
        $node = AdminNode::from($config, '@public/assets/');
        self::assertSame($config->path_public, $node->root);
        self::assertSame(AdminScope::public, $node->scope);
        self::assertSame($config->path_public . '/assets', $node->path);
        self::assertSame('/assets', $node->rel);
        self::assertSame('', $node->ext);
        self::assertTrue($node->dir);
    }

    public function testInstanceScopeFile(): void
    {
        $config = self::createConfigSite();
        $node = AdminNode::from($config, '@public/assets/favicon.svg');
        self::assertSame($config->path_public, $node->root);
        self::assertSame(AdminScope::public, $node->scope);
        self::assertSame($config->path_public . '/assets/favicon.svg', $node->path);
        self::assertSame('/assets/favicon.svg', $node->rel);
        self::assertSame('svg', $node->ext);
        self::assertFalse($node->dir);

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

    public function testFailureBadScope(): void
    {
        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Invalid path');
        $this->expectExceptionCode(StatusClientError::BadRequest->value);
        AdminNode::from(self::createConfigSite(), '@public');
    }

    public function testFailurePathTraversable(): void
    {
        $this->expectException(Problem::class);
        $this->expectExceptionMessage('Unauthorized access outside root paths.');
        $this->expectExceptionCode(StatusClientError::BadRequest->value);
        AdminNode::from(self::createConfigSite(), '@public/path/../../truc');
    }
}
