<?php

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Build;

final class PharBuilder
{
    private const COMPRESSION = \Phar::GZ;

    public function __construct()
    {
        if ($this->isPharReadonly()) {
            bye("⛔ You cannot build with phar being readonly.\n\nUsage:\n   php -d \"phar.readonly=Off\" build/build.php\n");
        }

        if (!$this->canCompress()) {
            bye("⛔ You cannot build with phar because the compression option is not supported.\n");
        }

        if (!is_file(PROJECT_DIR . '/' . BUILD_BOOTSTRAP_FILE)) {
            bye('⛔ The File ' . BUILD_BOOTSTRAP_FILE . ' does not exists.');
        }
    }

    public function build(?string $pharfile): void
    {
        try {
            if (null == $pharfile || 'phar' !== pathinfo($pharfile, PATHINFO_EXTENSION)) {
                bye('⛔ You MUST specify the target phar file inside the project.');
            }
            $pharfile = PROJECT_DIR . '/' . ltrim($pharfile, '/');

            $this->copyAllFilesToTmp();
            $this->unlinkPreviousBuilds($pharfile);

            $phar = new \Phar($pharfile);

            // Phars created from iterator (unlike from directory) does not have full-fledged directory structure.
            // For example, functions like opendir() will fail, although fopen() does not.
            // -> That's why we copy files + buildFromDirectory
            $phar->buildFromDirectory(BUILD_TMPDIR);

            $phar->setStub($this->getStub(basename($pharfile)));

            $phar->compressFiles(self::COMPRESSION);
        } catch (\Throwable $exception) {
            bye('⚠️ ' . $exception->getMessage());
        }
    }

    private function getStub(string $pharBasename): string
    {
        return '<?php // Generated ' . date('c') . "
if (class_exists('Phar')) {
Phar::mapPhar(" . var_export($pharBasename, true) . ");
Phar::interceptFileFuncs();
require 'phar://' . __FILE__ . '/" . BUILD_BOOTSTRAP_FILE . "';
}
__HALT_COMPILER(); ?>";
    }

    private function unlinkPreviousBuilds(string $pharfile): void
    {
        foreach (glob("$pharfile*") ?: [] as $filename) {
            @unlink($filename);
        }
    }

    private function cleanupTmp(): void
    {
        if (is_dir(BUILD_TMPDIR)) {
            exec('rm -Rf ' . escapeshellarg(BUILD_TMPDIR), $output, $code);
            if (0 !== $code) {
                throw new \LogicException('I was not able to cleanup the temporary folder ' . BUILD_TMPDIR);
            }
        }
        $this->mkdir(BUILD_TMPDIR);
    }

    private function copyAllFilesToTmp(): void
    {
        $this->cleanupTmp();
        foreach ($this->allfiles() as $file) {
            if (!str_starts_with($file->getPathname(), PROJECT_DIR . '/')) {
                continue;
            }

            $destPathname = BUILD_TMPDIR . substr($file->getPathname(), \strlen(PROJECT_DIR));

            $this->mkdir(\dirname($destPathname));
            if ('php' === strtolower($file->getExtension())) {
                file_put_contents($destPathname, php_strip_whitespace($file->getPathname()));
            } else {
                copy($file->getPathname(), $destPathname);
            }
        }
    }

    /**
     * @return \Iterator<\SplFileInfo>
     */
    public function allfiles(): BuildFilesIterator
    {
        return new BuildFilesIterator();
    }

    private function canCompress(): bool
    {
        return \Phar::canCompress(self::COMPRESSION);
    }

    private function isPharReadonly(): bool
    {
        return (bool) ini_get('phar.readonly');
    }

    private function mkdir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }
}
