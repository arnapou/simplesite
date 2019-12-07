<?php

use Arnapou\SimpleSite\Core\Controller;

return new class() extends Controller {

    public function configure(): void
    {
        $this->addRoute('hello-{name}', [$this, 'hello'], 'hello')
            ->setRequirement('name', '[a-zA-Z]+');
    }

    public function hello($name)
    {
        return $this->render('@templates/demo/hello.twig', ['name' => $name]);
    }
};