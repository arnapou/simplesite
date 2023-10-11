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

final readonly class BuildConfig
{
    /**
     * @param array<string> $includedDirectories
     * @param array<string> $ignoredFilenames
     * @param array<string> $ignoredPathMatch
     */
    public function __construct(
        public string $pharOutputFile,
        public string $pharBootstrap,
        public string $buildTempDir,
        public string $projectRootDir,
        public array $includedDirectories,
        public array $ignoredFilenames,
        public array $ignoredPathMatch,
    ) {
    }
}
