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

use Arnapou\SimpleSite\SimpleSite;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class YamlContext
{
    /** @var list<YamlContextLoader> */
    private array $loaders = [];

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
        $yamlFile = substr($view, 0, -\strlen(Utils::extension($view))) . 'yaml';
        try {
            if (SimpleSite::twigLoader()->exists($yamlFile)) {
                $yaml = SimpleSite::twigLoader()->getSourceContext($yamlFile)->getCode();
                $parsed = Yaml::parse($yaml);

                return \is_array($parsed) ? $parsed : [];
            }
        } catch (Throwable $exception) {
            $context = ['yaml' => $yamlFile, 'throwable' => $exception];
            SimpleSite::logger()->error('Yaml parsing failed', $context);
        }

        return [];
    }
}
