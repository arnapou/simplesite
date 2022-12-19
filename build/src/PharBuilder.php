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

use function dirname;
use function ini_get;

use LogicException;
use Phar;

use const PHP_VERSION;

use RuntimeException;

use function strlen;

use Throwable;

final class PharBuilder
{
    private const COMPRESSION = Phar::GZ;

    public function __construct(private readonly BuildConfig $config)
    {
        if ($this->isPharReadonly()) {
            bye("⛔ You cannot build with phar being readonly.\n\nUsage:\n   php -d \"phar.readonly=Off\" build/build.php\n");
        }

        if (!$this->canCompress()) {
            bye("⛔ You cannot build with phar because the compression option is not supported.\n");
        }
    }

    public function build(): void
    {
        try {
            $pharfile = $this->config->pharPath();

            $this->copyAllFilesToTmp();
            $this->unlinkPreviousBuilds($pharfile);

            $phar = new Phar($pharfile);

            // Phars created from iterator (unlike from directory) does not have full-fledged directory structure.
            // For example, functions like opendir() will fail, although fopen() does not.
            // -> That's why we copy files + buildFromDirectory
            $phar->buildFromDirectory($this->config->tempDir);

            $phar->setStub($this->getStub(basename($pharfile)));

            $phar->compressFiles(self::COMPRESSION);
        } catch (Throwable $exception) {
            bye('⚠️ ' . $exception->getMessage());
        }
    }

    private function getStub(string $pharBasename): string
    {
        return '<?php // Generated ' . date('c') . ' for PHP ' . PHP_VERSION . "
if (class_exists('Phar')) {
Phar::mapPhar(" . var_export($pharBasename, true) . ");
Phar::interceptFileFuncs();
require 'phar://' . __FILE__ . '/" . $this->config->bootstrapFile . "';
}
__HALT_COMPILER(); ?>";
    }

    private function unlinkPreviousBuilds(string $pharfile): void
    {
        foreach (glob("$pharfile*") ?: [] as $filename) {
            @unlink($filename);
        }
    }

    private function copyAllFilesToTmp(): void
    {
        $rootDir = $this->config->projectRootDir;
        $rootDirLength = strlen($rootDir);

        $this->cleanupTmp();
        foreach ($this->allfiles() as $file) {
            if (!str_starts_with($file->getPathname(), $rootDir . '/')) {
                continue;
            }

            $destPathname = $this->config->tempDir . substr($file->getPathname(), $rootDirLength);

            $this->mkdir(dirname($destPathname));
            if ('php' === strtolower($file->getExtension())) {
                file_put_contents($destPathname, php_strip_whitespace($file->getPathname()));
            } else {
                copy($file->getPathname(), $destPathname);
            }
        }
    }

    private function cleanupTmp(): void
    {
        if (is_dir($tempDir = $this->config->tempDir)) {
            exec('rm -Rf ' . escapeshellarg($tempDir), $output, $code);
            if (0 !== $code) {
                throw new LogicException("I was not able to cleanup the temporary folder $tempDir");
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
        return Phar::canCompress(self::COMPRESSION);
    }

    private function isPharReadonly(): bool
    {
        return (bool) ini_get('phar.readonly');
    }

    private function mkdir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }
}
