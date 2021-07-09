<?php

declare(strict_types=1);

namespace Solido\Common\RequestAdapter;

use Solido\Common\Exception\NonExistentFileException;
use Solido\Common\Exception\NonExistentParameterException;
use Solido\Common\ResponseAdapter\ResponseAdapterInterface;
use Solido\Common\ResponseAdapter\SymfonyHttpFoundationResponseAdapter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function assert;
use function is_string;

class SymfonyHttpFoundationRequestAdapter implements RequestAdapterInterface
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getContentType(): string
    {
        $contentType = $this->request->headers->get('Content-Type', 'application/x-www-form-urlencoded');
        assert(is_string($contentType));

        return $contentType;
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
        return $this->request->request->all();
    }

    public function hasRequestParam(string $name): bool
    {
        return $this->request->request->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams(): array
    {
        return $this->request->query->all();
    }

    public function hasQueryParam(string $name): bool
    {
        return $this->request->query->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParam(string $name)
    {
        if (! $this->request->query->has($name)) {
            throw new NonExistentParameterException($name);
        }

        return $this->request->query->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllFiles(): array
    {
        return $this->request->files->all();
    }

    public function hasFile(string $name): bool
    {
        return $this->request->files->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getFile(string $name)
    {
        if (! $this->hasFile($name)) {
            throw new NonExistentFileException($name);
        }

        return $this->request->files->get($name);
    }

    public function getRequestContent(): string
    {
        $content = $this->request->getContent(false);
        assert(is_string($content));

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public static function getUploadFileError($data): ?int
    {
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
