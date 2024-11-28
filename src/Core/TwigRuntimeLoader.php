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

namespace Arnapou\SimpleSite\Core;

use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigRuntimeLoader implements RuntimeLoaderInterface
{
    public function load(string $class)
    {
        return match ($class) {
            MarkdownRuntime::class => new MarkdownRuntime(new DefaultMarkdown()),
            default => null,
        };
    }
}
