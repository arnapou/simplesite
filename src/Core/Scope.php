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

use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;

enum Scope
{
    case data;
    case pages;
    case public;
    case templates;

    public static function default(): self
    {
        return self::pages;
    }

    public static function from(string $scope): self
    {
        return self::tryFrom($scope) ?? throw self::invalidScope($scope);
    }

    public static function tryFrom(string $scope): ?self
    {
        return match ($scope) {
            '@data' => self::data,
            '@pages' => self::pages,
            '@public' => self::public,
            '@templates' => self::templates,
            default => null,
        };
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return match ($this) {
            self::data => '@data',
            self::pages => '@pages',
            self::public => '@public',
            self::templates => '@templates',
        };
    }

    /**
     * @return ($strict is true ? non-empty-string : non-empty-string|null)
     */
    public function toPath(Config $config, bool $strict = true): ?string
    {
        return match ($this) {
            self::data => $config->path_data ?? (!$strict ? null : throw self::invalidScope($this->toString())),
            self::pages => $config->path_pages,
            self::public => $config->path_public,
            self::templates => $config->path_templates ?? (!$strict ? null : throw self::invalidScope($this->toString())),
        };
    }

    private static function invalidScope(string $scope): Problem
    {
        return new Problem(\sprintf('Invalid scope "%s".', $scope), StatusClientError::BadRequest);
    }
}
