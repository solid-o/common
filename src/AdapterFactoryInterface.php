<?php

declare(strict_types=1);

namespace Solido\Common;

use Solido\Common\RequestAdapter\RequestAdapterInterface;
use Solido\Common\ResponseAdapter\ResponseAdapterInterface;

interface AdapterFactoryInterface
{
    /**
     * Creates an adapter for the given request.
     */
    public function createRequestAdapter(object $request): RequestAdapterInterface;

    /**
     * Creates an adapter for the given response.
     */
    public function createResponseAdapter(object $response): ResponseAdapterInterface;

    /**
     * Whether the form data passed as argument is a file upload.
     */
    public function isFileUpload(mixed $data): bool;

    /**
     * Gets the upload file error from data.
     */
    public function getUploadFileError(mixed $data): int|null;
}
