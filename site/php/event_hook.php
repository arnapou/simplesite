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

use Arnapou\SimpleSite\Core\Event;
use Arnapou\SimpleSite\Core\Kernel;
use Arnapou\SimpleSite\Core\PhpCode;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Symfony\Component\HttpFoundation\Response;

return new class() implements PhpCode {
    public function init(ServiceContainer $container): void
    {
        $container->kernel()->eventListener()->addListener(Kernel::onRequest, [$this, 'onRequest']);
    }

    public function onRequest(Event $event): void
    {
        if ($event->getRequest()->get('killme')) {
            $event->setResponse(new Response('<h1>Arrrgghh .... I am killed ...</h1>', 500));
        }
    }
};
