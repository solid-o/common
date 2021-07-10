<?php

declare(strict_types=1);

namespace Solido\Common\ResponseAdapter;

use Psr\Http\Message\ResponseInterface;

class PsrResponseAdapter implements ResponseAdapterInterface
{
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function unwrap(): object
    {
        return $this->response;
    }

    public function getContentType(): string
    {
        $header = $this->response->getHeader('Content-Type');

        return $header[0] ?? 'application/octet-stream';
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaders(array $headers): ResponseAdapterInterface
    {
        foreach ($headers as $name => $values) {
            $this->response = $this->response->withHeader($name, $values);
        }

        return $this;
    }

    public function getContent(): string
    {
        return (string) $this->response->getBody();
    }
}
