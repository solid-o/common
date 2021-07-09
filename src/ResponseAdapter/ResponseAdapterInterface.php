<?php

declare(strict_types=1);

namespace Solido\Common\ResponseAdapter;

interface ResponseAdapterInterface
{
    /**
     * Unwraps the current response object.
     */
    public function unwrap(): object;

    /**
     * Sets the given headers into the response object.
     *
     * @param array<string, string|string[]> $headers
     */
    public function setHeaders(array $headers): self;
}
