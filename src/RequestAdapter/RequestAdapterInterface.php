<?php

declare(strict_types=1);

namespace Solido\Common\RequestAdapter;

interface RequestAdapterInterface
{
    /**
     * Gets the content type of the request.
     */
    public function getContentType(): string;

    /**
     * Gets the request method.
     */
    public function getRequestMethod(): string;

    /**
     * Gets the request (POST) parameters from the request.
     *
     * @return array<string, mixed>
     */
    public function getRequestParams(): array;

    /**
     * Checks if the request has the requested request (POST) parameter.
     */
    public function hasRequestParam(string $name): bool;

    /**
     * Gets the query parameters from the request.
     *
     * @return array<string, mixed>
     */
    public function getQueryParams(): array;

    /**
     * Checks if the request has the requested query parameter.
     */
    public function hasQueryParam(string $name): bool;

    /**
     * Returns the requested query parameter.
     * Will throw if parameter does not exist.
     *
     * @return mixed
     */
    public function getQueryParam(string $name);

    /**
     * Gets all the files of the request
     *
     * @return object[]
     */
    public function getAllFiles(): array;

    /**
     * Checks if the request contains the specified uploaded file
     */
    public function hasFile(string $name): bool;

    /**
     * Returns the requested uploaded.
     * Will throw if parameter does not exist.
     *
     * @return object|object[]
     */
    public function getFile(string $name);

    /**
     * Gets the request content as string.
     */
    public function getRequestContent(): string;

    /**
     * Gets the upload file error from data.
     *
     * @param mixed $data
     */
    public static function getUploadFileError($data): ?int;
}
