<?php

declare(strict_types=1);

namespace Solido\Common\ResponseAdapter;

use Symfony\Component\HttpFoundation\Response;

class SymfonyHttpFoundationResponseAdapter implements ResponseAdapterInterface
{
    private Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    public function unwrap(): object
    {
        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaders(array $headers): ResponseAdapterInterface
    {
        $this->response->headers->add($headers);

        return $this;
    }
}
