<?php

use Arnapou\SimpleSite\Core\Event;
use Arnapou\SimpleSite\Core\Kernel;
use Arnapou\SimpleSite\Core\PhpCode;
use Arnapou\SimpleSite\Core\ServiceContainer;
use Symfony\Component\HttpFoundation\Response;

return new class() implements PhpCode {

    public function init(ServiceContainer $container): void
    {
        $container->Kernel()->eventListener()->addListener(Kernel::onRequest, [$this, 'onRequest']);
    }

    public function onRequest(Event $event)
    {
        if ($event->getRequest()->get('killme')) {
            $event->setResponse(new Response('<h1>Arrrgghh .... I am killed ...</h1>', 500));
        }
    }
};