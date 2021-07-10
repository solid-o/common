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
     * Gets the content type of the response.
     */
    public function getContentType(): string;

    /**
     * Gets the response status code.
     */
    public function getStatusCode(): int;

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

    /**
     * Gets the response content as string.
     */
    public function getContent(): string;
}
