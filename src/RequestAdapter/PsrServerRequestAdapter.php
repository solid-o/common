<?php

declare(strict_types=1);

namespace Solido\Common\RequestAdapter;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Solido\Common\Exception\InvalidArgumentException;
use Solido\Common\Exception\NonExistentFileException;
use Solido\Common\Exception\NonExistentParameterException;
use Solido\Common\ResponseAdapter\PsrResponseAdapter;
use Solido\Common\ResponseAdapter\ResponseAdapterInterface;

use function array_key_exists;
use function get_object_vars;
use function is_object;

use const UPLOAD_ERR_OK;

class PsrServerRequestAdapter implements RequestAdapterInterface
{
    private ServerRequestInterface $request;
    private ?ResponseFactoryInterface $responseFactory;

    public function __construct(ServerRequestInterface $request, ?ResponseFactoryInterface $responseFactory)
    {
        $this->request = $request;
        $this->responseFactory = $responseFactory;
    }

    public function getContentType(): string
    {
        $header = $this->request->getHeader('Content-Type');

        return $header[0] ?? 'application/x-www-form-urlencoded';
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): array
    {
        return $this->request->getHeader($name);
    }

    public function getRequestMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParams(): array
    {
        $parsedBody = $this->request->getParsedBody();
        if ($parsedBody === null) {
            return [];
        }

        if (is_object($parsedBody)) {
            return get_object_vars($parsedBody);
        }

        return $parsedBody;
    }

    public function hasRequestParam(string $name): bool
    {
        $body = $this->getRequestParams();

        return array_key_exists($name, $body);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    public function hasQueryParam(string $name): bool
    {
        $params = $this->request->getQueryParams();

        return array_key_exists($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParam(string $name)
    {
        $params = $this->request->getQueryParams();
        if (! array_key_exists($name, $params)) {
            throw new NonExistentParameterException($name);
        }

        return $params[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllFiles(): array
    {
        return $this->request->getUploadedFiles();
    }

    public function hasFile(string $name): bool
    {
        $files = $this->request->getUploadedFiles();

        return array_key_exists($name, $files);
    }

    /**
     * {@inheritdoc}
     */
    public function getFile(string $name)
    {
        $files = $this->request->getUploadedFiles();
        if (! array_key_exists($name, $files)) {
            throw new NonExistentFileException($name);
        }

        return $files[$name];
    }

    public function getRequestContent(): string
    {
        return (string) $this->request->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public static function getUploadFileError($data): ?int
    {
        if (! $data instanceof UploadedFileInterface || $data->getError() === UPLOAD_ERR_OK) {
            return null;
        }

        return $data->getError();
    }

    public function createResponse(): ResponseAdapterInterface
    {
        if ($this->responseFactory === null) {
            throw new InvalidArgumentException('Could not find a response factory, response object cannot be created. Try to inject a valid response factory to the request adapter factory.');
        }

        return new PsrResponseAdapter($this->responseFactory->createResponse());
    }
}
