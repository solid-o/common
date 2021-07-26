<?php

declare(strict_types=1);

namespace Solido\Common\Tests\RequestAdapter;

use PHPUnit\Framework\TestCase;
use Solido\Common\Exception\InvalidArgumentException;
use Solido\Common\Exception\NonExistentFileException;
use Solido\Common\Exception\NonExistentParameterException;
use Solido\Common\RequestAdapter\SymfonyHttpFoundationRequestAdapter;
use Solido\Common\ResponseAdapter\SymfonyHttpFoundationResponseAdapter;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;
use const UPLOAD_ERR_OK;

class SymfonyHttpFoundationRequestAdapterTest extends TestCase
{
    public function testGetHeaderShouldReturnAnEmptyArrayIfHeaderIsNotPresent(): void
    {
        $adapter = new SymfonyHttpFoundationRequestAdapter(new Request());
        self::assertEquals([], $adapter->getHeader('X-Header'));
    }

    public function testGetHeaderShouldReturnAnArray(): void
    {
        $request = new Request();
        $request->headers->set('X-Header', 'custom-header');
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);
        self::assertEquals(['custom-header'], $adapter->getHeader('X-Header'));
    }

    public function getRequestParamsShouldReturnAnArray(): void
    {
        $request = new Request();
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        self::assertEquals([], $adapter->getRequestParams());

        $request = new Request();
        $request->request->replace(['test' => 'first']);
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        self::assertTrue($adapter->hasRequestParam('test'));
        self::assertEquals(['test' => 'first'], $adapter->getRequestParams());
    }

    public function testGetQueryParamsShouldThrowIfParamIsNonExistent(): void
    {
        $request = new Request();
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        $this->expectException(NonExistentParameterException::class);
        $this->expectExceptionCode(0);
        $this->expectErrorMessage('You have requested non-existent parameter "test"');

        $adapter->getQueryParam('test');
    }

    public function testGetQueryParamsShouldReturnTheRequestedQueryParameter(): void
    {
        $request = new Request();
        $request->query->set('test', '42');
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        self::assertSame('42', $adapter->getQueryParam('test'));
    }

    public function testGetFileShouldThrowIfFileIsNonExistent(): void
    {
        $request = new Request();
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        $this->expectException(NonExistentFileException::class);
        $this->expectExceptionCode(0);
        $this->expectErrorMessage('You have requested non-existent file "test"');

        $adapter->getFile('test');
    }

    public function testGetFileShouldReturnTheRequestedFile(): void
    {
        $request = new Request();
        $request->files->set('test', new UploadedFile(__FILE__, 'TEST', null, UPLOAD_ERR_OK, true));
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        self::assertInstanceOf(UploadedFile::class, $adapter->getFile('test'));
    }

    public function testGetContentType(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        self::assertEquals('application/json', $adapter->getContentType());
    }

    public function testGetRequestBody(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        self::assertEquals('{}', $adapter->getRequestContent());
    }

    public function testGetUploadFileErrorShouldThrowOnUnsupportedObjects(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SymfonyHttpFoundationRequestAdapter::getUploadFileError(new stdClass());
    }

    public function testCreateResponse(): void
    {
        $request = new Request();
        $adapter = new SymfonyHttpFoundationRequestAdapter($request);

        $response = $adapter->createResponse();
        self::assertInstanceOf(SymfonyHttpFoundationResponseAdapter::class, $response);
        self::assertInstanceOf(Response::class, $response->unwrap());
    }
}
