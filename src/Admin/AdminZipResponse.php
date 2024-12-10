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

use Arnapou\Psr\Psr7HttpMessage\FileResponse;
use Arnapou\Psr\Psr7HttpMessage\MimeType;
use Arnapou\Zip\Writing\Adapter\ZipStreamWriteOnly;
use Arnapou\Zip\ZipWriter;

final class AdminZipResponse extends FileResponse
{
    private readonly ZipWriter $zip;

    public function __construct(AdminNode $node, string $filename)
    {
        parent::__construct('', MimeType::detect($filename));

        set_time_limit(1800);
        $this->zip = new ZipWriter('php://output', new ZipStreamWriteOnly());
        $this->addNode($node, \strlen($node->pathname) + 1);
    }

    private function addNode(AdminNode $node, int $rootLength): void
    {
        if (!$node->isForbidden()) {
            if ($node->isDir) {
                foreach ($node->list() as $item) {
                    $this->addNode($item, $rootLength);
                }
            } else {
                $this->zip->addFile($node->pathname, substr($node->pathname, $rootLength));
            }
        }
    }
}
