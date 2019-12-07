<?php

use Arnapou\SimpleSite\Core\PhpCode;
use Arnapou\SimpleSite\Core\ServiceContainer;

return new class() implements PhpCode {

    public function init(ServiceContainer $container): void
    {
        $twig = $container->TwigEnvironment();

        $parameters = $container->Database()->getTable('twig_globals');
        foreach ($parameters as $key => $data) {
            $twig->addGlobal($key, $data['value'] ?? '');
        }
    }
};