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

namespace Arnapou\SimpleSite\Build;

use const PATHINFO_EXTENSION;

final readonly class BuildConfig
{
    public function __construct(
        public string $pharFile,
        public string $bootstrapFile,
        public string $projectRootDir,
        public string $tempDir,
        public readonly array $includedDirectories,
        public readonly array $ignoredFilenames,
        public readonly array $ignoredPathMatch,
    ) {
    }

    public function bootstrapPath(): string
    {
        $filepath = $this->projectRootDir . '/' . ltrim($this->bootstrapFile, '/');

        if (!is_file($filepath)) {
            bye("⛔ The File $this->bootstrapFile does not exists.");
        }

        return $filepath;
    }

    public function pharPath(): string
    {
        if ('' === $this->pharFile || 'phar' !== pathinfo($this->pharFile, PATHINFO_EXTENSION)) {
            bye('⛔ You MUST specify the target phar file inside the project.');
        }

        return $this->projectRootDir . '/' . ltrim($this->pharFile, '/');
    }
}
