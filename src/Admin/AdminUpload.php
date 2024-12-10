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

final class AdminUpload
{
    /** @var array<array{string, string}> */
    public array $errors = [];

    /** @var array<array{string, string}> */
    public array $warnings = [];

    /** @var array<array{string, string}> */
    public array $success = [];

    public function __construct(public readonly bool $unzip)
    {
    }

    public function addError(string $filename, string $detail): void
    {
        $this->errors[] = [$filename, $detail];
    }

    public function addWarning(string $filename, string $detail): void
    {
        $this->warnings[] = [$filename, $detail];
    }

    public function addSuccess(string $filename, string $detail): void
    {
        $this->success[] = [$filename, $detail];
    }

    public function isOk(): bool
    {
        return [] === $this->warnings && [] === $this->errors;
    }
}
