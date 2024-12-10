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

use Arnapou\SimpleSite\YamlContextLoader;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\Loader\LoaderInterface;

final class YamlContext
{
    /** @var list<YamlContextLoader> */
    private array $loaders = [];

    public function __construct(
        private readonly LoaderInterface $twigLoader,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function addLoader(YamlContextLoader $loader): self
    {
        $this->loaders[] = $loader;

        return $this;
    }

    /**
     * @param array<mixed> $context
     *
     * @return array<mixed>
     */
    public function getContext(string $view, array $context): array
    {
        $context = array_merge($context, $this->default($view));

        foreach ($this->loaders as $loader) {
            $context = $loader($view, $context);
        }

        return $context;
    }

    /**
     * @return array<mixed>
     */
    private function default(string $view): array
    {
        $helper = new Helper();
        $yamlFile = substr($view, 0, -\strlen($helper->fileExtension($view))) . 'yaml';
        try {
            if ($this->twigLoader->exists($yamlFile)) {
                return $helper->yamlParse($this->twigLoader->getSourceContext($yamlFile)->getCode());
            }
        } catch (Throwable $e) {
            $context = ['yaml' => $yamlFile, 'throwable' => $e];
            $this->logger->error('Yaml parsing failed', $context);
        }

        return [];
    }
}
