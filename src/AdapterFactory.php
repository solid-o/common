<?php

declare(strict_types=1);

namespace Solido\Common;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Solido\Common\Exception\UnsupportedRequestObjectException;
use Solido\Common\Exception\UnsupportedResponseObjectException;
use Solido\Common\RequestAdapter\PsrServerRequestAdapter;
use Solido\Common\RequestAdapter\RequestAdapterInterface;
use Solido\Common\RequestAdapter\SymfonyHttpFoundationRequestAdapter;
use Solido\Common\ResponseAdapter\PsrResponseAdapter;
use Solido\Common\ResponseAdapter\ResponseAdapterInterface;
use Solido\Common\ResponseAdapter\SymfonyHttpFoundationResponseAdapter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

class AdapterFactory implements AdapterFactoryInterface
{
    public function __construct(private readonly ResponseFactoryInterface|null $responseFactory = null)
    {
    }

    public function createRequestAdapter(object $request): RequestAdapterInterface
    {
        if ($request instanceof Request) {
            return new SymfonyHttpFoundationRequestAdapter($request);
        }

        if ($request instanceof ServerRequestInterface) {
            return new PsrServerRequestAdapter($request, $this->responseFactory);
        }

        throw new UnsupportedRequestObjectException(
            sprintf('Cannot create an adapter for the request class "%s"', $request::class),
        );
    }

    public function createResponseAdapter(object $response): ResponseAdapterInterface
    {
        if ($response instanceof Response) {
            return new SymfonyHttpFoundationResponseAdapter($response);
        }

        if ($response instanceof ResponseInterface) {
            return new PsrResponseAdapter($response);
        }

        throw new UnsupportedResponseObjectException(
            sprintf('Cannot create an adapter for the response class "%s"', $response::class),
        );
    }

    public function isFileUpload(mixed $data): bool
    {
        return $data instanceof File || $data instanceof UploadedFileInterface;
    }

    public function getUploadFileError(mixed $data): int|null
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
