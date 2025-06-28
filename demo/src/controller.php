<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <me@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Controller;

return new class() extends Controller {
    public function configure(): void
    {
        $this->addRoute('hello-{name}', $this->hello(...), 'hello')
            ->setRequirement('name', '[a-zA-Z]+');
    }

    public function hello(string $name): Response
    {
        $this->logger()->debug("Hello $name");
        $this->logger()->info("Hello $name");
        $this->logger()->notice("Hello $name");
        $this->logger()->warning("Hello $name");
        $this->logger()->error("Hello $name");
        $this->logger()->critical("Hello $name");
        $this->logger()->alert("Hello $name");
        $this->logger()->emergency("Hello $name");

        return $this->render('@templates/demo/hello.twig', ['name' => $name]);
    }
};
