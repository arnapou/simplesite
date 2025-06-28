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

use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class AdminConfigTest extends TestCase
{
    use ConfigTestTrait;

    protected function setUp(): void
    {
        [, $config] = self::createConfigTest();
        $container = self::resetContainer();
        $container->registerInstance(Config::class, $config);
    }

    #[RunInSeparateProcess]
    public function testSuccessSort(): void
    {
        $adminConfig = $this->getAdminConfig();
        $adminConfig->setRedirects([
            ['link' => 'baz', 'from' => 'qux', 'dummy' => 123],
            ['from' => 'foo', 'link' => 'bar'],
        ]);

        self::assertSame(
            [
                ['from' => 'foo', 'link' => 'bar'],
                ['from' => 'qux', 'link' => 'baz'],
            ],
            $adminConfig->getRedirects(),
        );
    }

    #[RunInSeparateProcess]
    public function testErrorFrom(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Missing "from".');
        $this->getAdminConfig()->setRedirects([
            ['link' => 'foo', 'dummy' => 'dummy'],
        ]);
    }

    #[RunInSeparateProcess]
    public function testErrorTo(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Missing "link".');
        $this->getAdminConfig()->setRedirects([
            ['from' => 'foo', 'dummy' => 'dummy'],
        ]);
    }

    #[RunInSeparateProcess]
    public function testErrorBadItem(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('The redirect item is not an array.');
        $this->getAdminConfig()->setRedirects([
            123,
        ]);
    }

    #[RunInSeparateProcess]
    public function testErrorDuplicate(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Duplicate from:');
        $this->getAdminConfig()->setRedirects([
            ['from' => 'foo', 'link' => 'bar'],
            ['from' => '/foo', 'link' => 'baz'],
        ]);
    }
}
