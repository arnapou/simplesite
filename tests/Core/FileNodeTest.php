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

namespace Arnapou\SimpleSite\Tests\Core;

use Arnapou\SimpleSite\Core\FileNode;
use DomainException;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;

class FileNodeTest extends TestCase
{
    public function testRootIsNotAFolder(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('is not a folder');
        new FileNode('/do/not/exists!', '');
    }

    public function testUnauthorizedAccessOutsideRootPaths(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Unauthorized access outside root paths');
        new FileNode('/tmp', '/foo/' . str_repeat('../', 20) . 'bar');
    }

    public function testPathTraversableIsForbidden(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Path traversable is forbidden');
        new FileNode('/tmp', '/foo/' . str_repeat('../', 21) . 'bar');
    }

    public function testEmpty(): void
    {
        $node = new FileNode('/tmp/', '/');

        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => '/',
                'fullPath' => '/tmp',
                'exists' => true,
                'isFolder' => true,
                'isFile' => false,
            ],
            (array) $node,
        );
    }

    public function testFileRelative(): void
    {
        [$folder, $file] = $this->getRandomTmpFile(exists: true);
        $node = new FileNode('/tmp/', "$folder/../../$file");

        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => "/$file",
                'fullPath' => "/tmp/$file",
                'exists' => false,
                'isFolder' => false,
                'isFile' => true,
            ],
            (array) $node,
        );
    }

    public function testNewRelative(): void
    {
        [$folder, $file] = $this->getRandomTmpFile(exists: true);
        $node = new FileNode('/tmp/', "$folder/$file");

        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => "/$folder/$file",
                'fullPath' => "/tmp/$folder/$file",
                'exists' => true,
                'isFolder' => false,
                'isFile' => true,
            ],
            (array) $node,
        );
        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => '/test/test.XYZ',
                'fullPath' => '/tmp/test/test.XYZ',
                'exists' => false,
                'isFolder' => false,
                'isFile' => true,
            ],
            (array) $node->newRelative('/../test.XYZ'),
        );
        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => '/Bar',
                'fullPath' => '/tmp/Bar',
                'exists' => false,
                'isFolder' => true,
                'isFile' => false,
            ],
            (array) $node->newRelative('///..//../Bar/'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Unauthorized access outside root paths');
        $node->newRelative('../../../usr/foo.txt');
    }

    public function testFileExists(): void
    {
        [$folder, $file] = $this->getRandomTmpFile(exists: true);
        $node = new FileNode('/tmp/', "$folder/$file");

        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => "/$folder/$file",
                'fullPath' => "/tmp/$folder/$file",
                'exists' => true,
                'isFolder' => false,
                'isFile' => true,
            ],
            (array) $node,
        );
        self::assertSame('sVg', $node->extension(), 'extension');
        self::assertSame(0, $node->filesize(), 'filesize');
        self::assertSame($file, $node->basename(), 'basename');
    }

    public function testFolderExists(): void
    {
        [$folder] = $this->getRandomTmpFile(exists: true);
        $node = new FileNode('/tmp/', $folder);

        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => "/$folder",
                'fullPath' => "/tmp/$folder",
                'exists' => true,
                'isFolder' => true,
                'isFile' => false,
            ],
            (array) $node,
        );
        self::assertNull($node->extension(), 'extension');
        self::assertNull($node->filesize(), 'filesize');
        self::assertSame(basename($folder), $node->basename(), 'basename');
    }

    public function testFileNotExists(): void
    {
        [$folder, $file] = $this->getRandomTmpFile(exists: false);
        $node = new FileNode('/tmp/', "$folder/$file");

        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => "/$folder/$file",
                'fullPath' => "/tmp/$folder/$file",
                'exists' => false,
                'isFolder' => false,
                'isFile' => true,
            ],
            (array) $node,
        );
        self::assertSame('sVg', $node->extension(), 'extension');
        self::assertNull($node->filesize(), 'filesize');
        self::assertSame($file, $node->basename(), 'basename');
    }

    public function testFolderNotExists(): void
    {
        [$folder] = $this->getRandomTmpFile(exists: false);
        $node = new FileNode('/tmp/', "$folder/");

        self::assertSame(
            [
                'root' => '/tmp',
                'relativePath' => "/$folder",
                'fullPath' => "/tmp/$folder",
                'exists' => false,
                'isFolder' => true,
                'isFile' => false,
            ],
            (array) $node,
        );
        self::assertNull($node->extension(), 'extension');
        self::assertNull($node->filesize(), 'filesize');
        self::assertSame(basename($folder), $node->basename(), 'basename');
    }

    /**
     * @return array{string, string}
     */
    private function getRandomTmpFile(bool $exists): array
    {
        $rand = fn () => new Randomizer()->getBytesFromString('0123456789abcdefghijklmnoqprstuvwxyzABCDEFGHIJKLMNOQPRSTUVWXYZ', 8);
        $folder = 'test/' . $rand();
        $file = $rand() . '.sVg';
        if ($exists) {
            @mkdir("/tmp/$folder", 0o777, true);
            touch("/tmp/$folder/$file");
        }

        return [$folder, $file];
    }
}
