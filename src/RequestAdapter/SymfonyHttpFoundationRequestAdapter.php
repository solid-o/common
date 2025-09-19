<?php

declare(strict_types=1);

namespace Solido\Common\RequestAdapter;

use Solido\Common\Exception\InvalidArgumentException;
use Solido\Common\Exception\NonExistentFileException;
use Solido\Common\Exception\NonExistentParameterException;
use Solido\Common\ResponseAdapter\ResponseAdapterInterface;
use Solido\Common\ResponseAdapter\SymfonyHttpFoundationResponseAdapter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function assert;
use function get_debug_type;
use function is_numeric;
use function is_string;
use function sprintf;

class SymfonyHttpFoundationRequestAdapter implements RequestAdapterInterface
{
    public function __construct(private readonly Request $request)
    {
    }

    public function getContentType(): string
    {
        $contentType = $this->request->headers->get('Content-Type', 'application/x-www-form-urlencoded');
        assert(is_string($contentType));

        return $contentType;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeader(string $name): array
    {
        /* @phpstan-ignore-next-line */
        return $this->request->headers->all($name);
    }

    public function getRequestMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestParams(): array
    {
        return $this->request->request->all();
    }

    public function hasRequestParam(string $name): bool
    {
        return $this->request->request->has($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryParams(): array
    {
        return $this->request->query->all();
    }

    public function hasQueryParam(string $name): bool
    {
        return $this->request->query->has($name);
    }

    public function getQueryParam(string $name): mixed
    {
        if (! $this->request->query->has($name)) {
            throw new NonExistentParameterException($name);
        }

        return $this->request->query->get($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllFiles(): array
    {
        return $this->request->files->all();
    }

    public function hasFile(string $name): bool
    {
        return $this->request->files->has($name);
    }

    public function getFile(string $name): object|array
    {
        if (! $this->hasFile($name)) {
            throw new NonExistentFileException($name);
        }

        /** @var File[] | File $value */
        $value = $this->request->files->get($name);

        return $value;
    }

    public function getRequestContent(): string
    {
        return $this->request->getContent(false);
    }

    public function getRequestContentLength(): int
    {
        $length = $this->request->server->get('CONTENT_LENGTH', 0);
        assert(is_numeric($length));

        return (int) $length;
    }

    public static function getUploadFileError(mixed $data): int|null
    {
        if (! $data instanceof File) {
            throw new InvalidArgumentException(sprintf('Invalid uploaded file object. Expected Symfony\Component\HttpFoundation\File\UploadedFile, %s given', get_debug_type($data)));
        }

        if (! $data instanceof UploadedFile || $data->isValid()) {
            return null;
        }

        return $data->getError();
    }

    public function createResponse(): ResponseAdapterInterface
    {
        return new SymfonyHttpFoundationResponseAdapter(new Response());
    }
}
