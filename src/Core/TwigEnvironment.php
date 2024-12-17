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

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\Markdown\ErusevMarkdown;
use Twig\Extra\Markdown\LeagueMarkdown;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Extra\Markdown\MichelfMarkdown;
use Twig\Loader\LoaderInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

final class TwigEnvironment extends Environment
{
    public function __construct(
        LoaderInterface $loader,
        Config $config,
        TwigExtension $twigExtension,
    ) {
        parent::__construct(
            $loader,
            [
                'debug' => true,
                'charset' => 'UTF-8',
                'strict_variables' => false,
                'autoescape' => 'html',
                'cache' => $config->pathCache('twig'),
                'auto_reload' => true,
                'optimizations' => -1,
            ],
        );
        $this->addRuntimeLoader($this->createRuntimeLoader());
        $this->addExtension(new DebugExtension());
        $this->addExtension(new MarkdownExtension());
        $this->addExtension($twigExtension);
    }

    private function createRuntimeLoader(): RuntimeLoaderInterface
    {
        return new class implements RuntimeLoaderInterface {
            public function load(string $class)
            {
                return match ($class) {
                    // MarkdownRuntime::class => new MarkdownRuntime(new MichelfMarkdown()),
                    // ├─ composer req michelf/php-markdown
                    // ╰─ the generation is less conventional with additional <br> etc ...
                    // MarkdownRuntime::class => new MarkdownRuntime(new LeagueMarkdown()),
                    // ├─ composer req league/commonmark
                    // ╰─ WTF !!! 190 KB more code compressed in the phar !
                    MarkdownRuntime::class => new MarkdownRuntime(new ErusevMarkdown()),
                    // ├─ composer req erusev/parsedown
                    // ╰─ works but last stable 1.7.4 is 5 years old, YOLO @master ¯\_(ツ)_/¯
                    default => null,
                };
            }
        };
    }
}
