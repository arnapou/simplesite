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

use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtensionTest extends TestCase
{
    use ConfigTestTrait;

    protected function setUp(): void
    {
        $container = self::resetContainer();
        $container->registerInstance(Config::class, self::createConfigDemo());
    }

    #[RunInSeparateProcess]
    public function testHasFilters(): void
    {
        self::assertSame(
            [
                'basename',
                'camel',
                'debug_type',
                'dirname',
                'getenv',
                'minify_html',
                'path_dir',
                'path_page',
                'slug',
                'snake',
                'thumbnail',
                'view',
                'yaml',
            ],
            array_map(
                static fn (TwigFilter $filter) => $filter->getName(),
                $this->getTwigExtension()->getFilters(),
            ),
        );
    }

    #[RunInSeparateProcess]
    public function testHasFunctions(): void
    {
        self::assertSame(
            [
                'asset',
                'data',
                'path',
                'path_dir',
                'path_page',
                'thumbnail',
            ],
            array_map(
                static fn (TwigFunction $function) => $function->getName(),
                $this->getTwigExtension()->getFunctions(),
            ),
        );
    }
}
