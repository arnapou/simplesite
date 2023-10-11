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

use Arnapou\Psr\Psr14EventDispatcher\Event\ServerRequestEvent;
use Arnapou\Psr\Psr14EventDispatcher\Listener\ServerRequestListenerInterface;
use Arnapou\Psr\Psr7HttpMessage\HtmlResponse;
use Arnapou\SimpleSite;
use Arnapou\SimpleSite\PhpCode;

return new class() implements PhpCode {
    public function init(): void
    {
        SimpleSite::router()->addListener($this->getHackListener());
    }

    private function getHackListener(): ServerRequestListenerInterface
    {
        return new class() implements ServerRequestListenerInterface {
            public function __invoke(ServerRequestEvent $event): void
            {
                if ($event->request->getQueryParams()['killme'] ?? false) {
                    $event->response = new HtmlResponse('<h1>Arrrgghh .... I am killed ...</h1>', 500);
                }
            }
        };
    }
};
