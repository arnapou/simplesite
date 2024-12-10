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

namespace Arnapou\SimpleSite\Admin;

use Arnapou\Psr\Psr7HttpMessage\Status\StatusClientError;
use Arnapou\SimpleSite\Core\Config;
use Arnapou\SimpleSite\Core\Problem;

enum AdminScope
{
    case pages;
    case public;
    case templates;

    public static function tryFrom(string $scope): ?self
    {
        return match ($scope) {
            '@pages' => self::pages,
            '@public' => self::public,
            '@templates' => self::templates,
            default => null,
        };
    }

    public static function from(string $scope): self
    {
        return self::tryFrom($scope) ?? self::throwInvalid($scope);
    }

    public function toPath(Config $config): string
    {
        return match ($this) {
            self::pages => $config->path_pages,
            self::public => $config->path_public,
            self::templates => $config->path_templates ?? self::throwInvalid('@templates'),
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::pages => '@pages',
            self::public => '@public',
            self::templates => '@templates',
        };
    }

    private static function throwInvalid(string $scope): never
    {
        throw new Problem("Invalid scope '$scope'.", StatusClientError::BadRequest);
    }
}
