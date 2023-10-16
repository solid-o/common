<?php

declare(strict_types=1);

namespace Solido\Common\ResponseAdapter;

use Psr\Http\Message\ResponseInterface;

use function array_combine;
use function array_keys;
use function array_map;

class PsrResponseAdapter implements ResponseAdapterInterface
{
    public function __construct(private ResponseInterface $response)
    {
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
        $headers = $this->response->getHeaders();

        return array_combine(
            array_map('strtolower', array_keys($headers)),
            $headers,
        );
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
