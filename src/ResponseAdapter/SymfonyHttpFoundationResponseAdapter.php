<?php

declare(strict_types=1);

namespace Solido\Common\ResponseAdapter;

use Symfony\Component\HttpFoundation\Response;

class SymfonyHttpFoundationResponseAdapter implements ResponseAdapterInterface
{
    private Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function unwrap(): object
    {
        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->response->headers->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): array
    {
        if (! $this->response->headers->has($name)) {
            return [];
        }

        return $this->response->headers->all($name);
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
