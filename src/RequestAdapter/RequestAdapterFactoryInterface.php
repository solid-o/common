<?php

declare(strict_types=1);

namespace Solido\Common\RequestAdapter;

interface RequestAdapterFactoryInterface
{
    /**
     * Creates an adapter for the given request.
     */
    public function factory(object $request): RequestAdapterInterface;

    /**
     * Whether the form data passed as argument is a file upload.
     *
     * @param mixed $data
     */
    public function isFileUpload($data): bool;

    /**
     * Gets the upload file error from data.
     *
     * @param mixed $data
     */
    public function getUploadFileError($data): ?int;
}
