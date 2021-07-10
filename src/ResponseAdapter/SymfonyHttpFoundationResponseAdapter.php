<?php

declare(strict_types=1);

namespace Solido\Common\ResponseAdapter;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function ob_get_clean;
use function ob_start;

class SymfonyHttpFoundationResponseAdapter implements ResponseAdapterInterface
{
    private Response $response;
    private string $content;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function unwrap(): object
    {
        return $this->response;
    }

    public function getContentType(): string
    {
        return (string) $this->response->headers->get('Content-Type', 'application/octet-stream');
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

    public function getContent(): string
    {
        if ($this->response instanceof StreamedResponse || $this->response instanceof BinaryFileResponse) {
            if (! isset($this->content)) {
                ob_start();
                $this->response->sendContent();
                $this->content = (string) ob_get_clean();
            }

            return $this->content;
        }

        return (string) $this->response->getContent();
    }
}
