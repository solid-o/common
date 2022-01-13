<?php

declare(strict_types=1);

namespace Solido\Common\Tests\Form;

use Solido\Common\Exception\InvalidArgumentException;
use stdClass;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

abstract class HttpFoundationRequestHandlerTest extends AbstractRequestHandlerTest
{
    public function testRequestShouldBeInstanceOfRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->requestHandler->handleRequest($this->createForm('name', 'GET'), new stdClass());
    }

    protected function setRequestData($method, $data, $files = []): void
    {
        $this->request = Request::create('http://localhost', $method, $data, [], $files, [], http_build_query($data));
    }

    abstract protected function getRequestHandler(): RequestHandlerInterface;

    protected function getUploadedFile($suffix = ''): UploadedFile
    {
        return new UploadedFile(__DIR__ . '/../Fixtures/foo' . $suffix, 'foo' . $suffix);
    }

    protected function getInvalidFile(): string
    {
        return 'file:///etc/passwd';
    }

    protected function getFailedUploadedFile($errorCode): UploadedFile
    {
        return new UploadedFile(__DIR__ . '/../Fixtures/foo', 'foo', null, $errorCode, true);
    }
}
