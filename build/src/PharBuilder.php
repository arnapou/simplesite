<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <me@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Build;

use Arnapou\SimpleSite\Core;
use Arnapou\SimpleSite\SimpleSite;

final class PharBuilder
{
    private const int COMPRESSION = \Phar::GZ;

    public function __construct(private readonly BuildConfig $config)
    {
        if ('cli' !== \PHP_SAPI) {
            $this->bye('⛔ This script should be run only in CLI');
        }

        if ($this->isPharReadonly()) {
            $this->bye("⛔ You cannot build with phar being readonly.\n\nUsage:\n   php -d \"phar.readonly=Off\" build/build.php\n");
        }

        if (!$this->canCompress()) {
            $this->bye("⛔ You cannot build with phar because the compression option is not supported.\n");
        }

        if ('' === $this->config->pharOutputFile || 'phar' !== pathinfo($this->config->pharOutputFile, \PATHINFO_EXTENSION)) {
            $this->bye('⛔ You MUST specify the target phar file inside the project.');
        }
    }

    public function build(): void
    {
        try {
            $pharfile = $this->config->projectRootDir . '/' . ltrim($this->config->pharOutputFile, '/');

            $this->copyAllFilesToTmp();
            $this->unlinkPreviousBuilds($pharfile);

            $phar = new \Phar($pharfile);

            // Phars created from iterator (unlike from directory) does not have full-fledged directory structure.
            // For example, functions like opendir() will fail, although fopen() does not.
            // -> That's why we copy files + buildFromDirectory
            $phar->buildFromDirectory($this->config->buildTempDir);

            $phar->setStub($this->getStub(basename($pharfile)));

            $phar->compressFiles(self::COMPRESSION);
        } catch (\Throwable $exception) {
            $this->bye('⚠️ ' . $exception->getMessage());
        }
    }

    private function getStub(string $pharBasename): string
    {
        return '<?php // Generated ' . gmdate('Y-m-d H:i:s') . ' UTC ┃ 🐘 PHP ' . \PHP_VERSION . ' ┃ 🚀 Simplesite ' . SimpleSite::version() . "
if (class_exists('Phar')) {
Phar::mapPhar(" . var_export($pharBasename, true) . ");
Phar::interceptFileFuncs();
require 'phar://' . __FILE__ . '/" . $this->config->pharBootstrap . "';
}
__HALT_COMPILER(); ?>";
    }

    private function unlinkPreviousBuilds(string $pharfile): void
    {
        $files = glob("$pharfile*");
        if (\is_array($files)) {
            foreach ($files as $filename) {
                @unlink($filename);
            }
        }
    }

    private function getHelper(): Core\Helper
    {
        SimpleSite::container()->registerInstance(
            Core\Config::class,
            new Core\Config(
                path_public: __DIR__ . '/../../demo/public',
                path_pages: __DIR__ . '/../../demo/pages',
                path_cache: '/tmp/simplesite',
            ),
        );

        return SimpleSite::helper();
    }

    private function copyAllFilesToTmp(): void
    {
        $rootDir = $this->config->projectRootDir;
        $minifyHtml = $this->getHelper()->minifyHtml(...);

        $this->cleanupTmp();
        foreach ($this->allfiles() as $file) {
            if (!str_starts_with($file->getPathname(), $rootDir . '/')) {
                continue;
            }

            $destPathname = $this->config->buildTempDir . substr($file->getPathname(), \strlen($rootDir));
            $this->mkdir(\dirname($destPathname));
            $contents = (string) file_get_contents($file->getPathname());

            file_put_contents(
                $destPathname,
                match (strtolower($file->getExtension())) {
                    'php' => php_strip_whitespace($file->getPathname()),
                    'twig', 'svg' => $minifyHtml($contents),
                    'css' => preg_replace('!/\*.*?\*/!', '', $minifyHtml($contents)),
                    default => $contents,
                },
            );
        }
    }

    private function cleanupTmp(): void
    {
        if (is_dir($tempDir = $this->config->buildTempDir)) {
            exec('rm -Rf ' . escapeshellarg($tempDir), $output, $code);
            if (0 !== $code) {
                throw new \LogicException("I was not able to cleanup the temporary folder $tempDir");
            }
        }
        $this->mkdir($tempDir);
    }

    public function allfiles(): BuildFilesIterator
    {
        return new BuildFilesIterator($this->config);
    }

    private function canCompress(): bool
    {
        return \Phar::canCompress(self::COMPRESSION);
    }

    private function isPharReadonly(): bool
    {
        return (bool) \ini_get('phar.readonly');
    }

    private function mkdir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            $this->bye("❌ Directory '$dir' could not be created");
        }
    }

    private function bye(string $msg): never
    {
        echo "$msg\n";
        exit(1);
    }
}
