<?php

use Arnapou\SimpleSite\Core\Controller;

return new class() extends Controller {

    public function configure(): void
    {
        $this->addRoute(
            'je_plante',
            function () {
                throw new \RuntimeException("Plantage volontaire");
            },
            'je_plante'
        );
    }
};