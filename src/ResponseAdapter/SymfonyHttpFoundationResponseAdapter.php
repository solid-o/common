<?php

declare(strict_types=1);

namespace Solido\Common\ResponseAdapter;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function assert;
use function is_string;
use function ob_get_clean;
use function ob_get_level;
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
        $header = $this->response->headers->get('Content-Type', 'application/octet-stream');
        assert(is_string($header));

        return $header;
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
                assert($obLevel = ob_get_level() >= 0);
                ob_start();

                $this->response->sendContent();

                assert($obLevel + 1 === ob_get_level());
                $content = ob_get_clean();

                assert(is_string($content));
                $this->content = $content;
            }

            return $this->content;
        }

        return $this->response->getContent() ?: '';
    }
}
