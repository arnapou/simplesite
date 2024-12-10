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

namespace Arnapou\SimpleSite\Tests\Admin;

use Arnapou\SimpleSite\Admin\AdminUpload;
use PHPUnit\Framework\TestCase;

class AdminUploadTest extends TestCase
{
    public function testBehaviour(): void
    {
        $upload = new AdminUpload(true);

        self::assertTrue($upload->unzip);
        self::assertSame([], $upload->errors);
        self::assertSame([], $upload->warnings);
        self::assertSame([], $upload->success);

        $upload->addSuccess('success 1', 'detail 1');
        $upload->addSuccess('success 2', 'detail 2');
        self::assertTrue($upload->isOk());
        self::assertSame([], $upload->errors);
        self::assertSame([], $upload->warnings);
        self::assertSame([['success 1', 'detail 1'], ['success 2', 'detail 2']], $upload->success);

        $upload->addWarning('warn 1', 'detail 1');
        $upload->addWarning('warn 2', 'detail 2');
        self::assertFalse($upload->isOk());
        self::assertSame([], $upload->errors);
        self::assertSame([['warn 1', 'detail 1'], ['warn 2', 'detail 2']], $upload->warnings);
        self::assertSame([['success 1', 'detail 1'], ['success 2', 'detail 2']], $upload->success);

        $upload->addError('error 1', 'detail 1');
        $upload->addError('error 2', 'detail 2');
        self::assertFalse($upload->isOk());
        self::assertSame([['error 1', 'detail 1'], ['error 2', 'detail 2']], $upload->errors);
        self::assertSame([['warn 1', 'detail 1'], ['warn 2', 'detail 2']], $upload->warnings);
        self::assertSame([['success 1', 'detail 1'], ['success 2', 'detail 2']], $upload->success);
    }
}
