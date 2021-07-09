<?php

declare(strict_types=1);

namespace Solido\Common;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Solido\BodyConverter\Exception\UnsupportedRequestObjectException;
use Solido\Common\RequestAdapter\PsrServerRequestAdapter;
use Solido\Common\RequestAdapter\RequestAdapterInterface;
use Solido\Common\RequestAdapter\SymfonyHttpFoundationRequestAdapter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

use function get_class;
use function Safe\sprintf;

class AdapterFactory implements AdapterFactoryInterface
{
    private ?ResponseFactoryInterface $responseFactory;

    public function __construct(?ResponseFactoryInterface $responseFactory = null)
    {
        $this->responseFactory = $responseFactory;
    }

    public function factory(object $request): RequestAdapterInterface
    {
        if ($request instanceof Request) {
            return new SymfonyHttpFoundationRequestAdapter($request);
        }

        if ($request instanceof ServerRequestInterface) {
            return new PsrServerRequestAdapter($request, $this->responseFactory);
        }

        throw new UnsupportedRequestObjectException(
            sprintf('Cannot create an adapter for the request class "%s"', get_class($request))
        );
    }

    /**
     * @param mixed $data
     */
    public function isFileUpload($data): bool
    {
        return $data instanceof File || $data instanceof UploadedFileInterface;
    }

    /**
     * @param mixed $data
     */
    public function getUploadFileError($data): ?int
    {
        if ($data instanceof File) {
            return SymfonyHttpFoundationRequestAdapter::getUploadFileError($data);
        }

        if ($data instanceof UploadedFileInterface) {
            return PsrServerRequestAdapter::getUploadFileError($data);
        }

        return null;
    }
}
