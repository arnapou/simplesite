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

namespace Arnapou\SimpleSite\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Event
{
    private readonly Request $request;

    public function __construct(
        private readonly ServiceContainer $container,
        private ?Response $response
    ) {
        $this->request = $container->request();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): self
    {
        $this->response = $response;

        return $this;
    }
}
