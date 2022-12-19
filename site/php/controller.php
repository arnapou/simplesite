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

use Arnapou\SimpleSite\Core\Controller;
use Symfony\Component\HttpFoundation\Response;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute('hello-{name}', [$this, 'hello'], 'hello')
            ->setRequirement('name', '[a-zA-Z]+');
    }

    public function hello(string $name): Response
    {
        $this->container()->logger()->debug("Hello $name");
        $this->container()->logger()->info("Hello $name");
        $this->container()->logger()->notice("Hello $name");
        $this->container()->logger()->warning("Hello $name");
        $this->container()->logger()->error("Hello $name");
        $this->container()->logger()->critical("Hello $name");
        $this->container()->logger()->alert("Hello $name");
        $this->container()->logger()->emergency("Hello $name");

        return $this->render('@templates/demo/hello.twig', ['name' => $name]);
    }
};
