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
     * Gets all the headers from the response object.
     *
     * @return array<string, string|string[]>
     */
    public function getHeaders(): array;

    /**
     * Gets the specified header. Always returns an array of strings.
     *
     * @return string[]
     */
    public function getHeader(string $name): array;

    /**
     * Sets the given headers into the response object.
     *
     * @param array<string, string|string[]> $headers
     */
    public function setHeaders(array $headers): self;
}
