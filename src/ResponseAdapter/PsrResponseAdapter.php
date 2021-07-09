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
}
