<?php

declare(strict_types=1);

namespace Solido\Common\Tests\RequestAdapter;

use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Solido\Common\Exception\InvalidArgumentException;
use Solido\Common\Exception\NonExistentFileException;
use Solido\Common\Exception\NonExistentParameterException;
use Solido\Common\RequestAdapter\PsrServerRequestAdapter;
use stdClass;

use const UPLOAD_ERR_OK;

class PsrServerRequestAdapterTest extends TestCase
{
    public function testGetHeaderShouldReturnAnEmptyArrayIfHeaderIsNotPresent(): void
    {
        $adapter = new PsrServerRequestAdapter(new ServerRequest('GET', '/'), null);
        self::assertEquals([], $adapter->getHeader('X-Header'));
    }

    public function testGetHeaderShouldReturnAnArray(): void
    {
        $adapter = new PsrServerRequestAdapter(new ServerRequest('GET', '/', ['X-Header' => 'custom-header']), null);
        self::assertEquals(['custom-header'], $adapter->getHeader('X-Header'));
    }

    public function testGetRequestParamsShouldReturnAnArray(): void
    {
        $request = new ServerRequest('GET', '/', ['Content-Type' => 'application/json']);
        $adapter = new PsrServerRequestAdapter($request, null);

        self::assertEquals([], $adapter->getRequestParams());

        $request = $request->withParsedBody(['test' => 'first']);
        $adapter = new PsrServerRequestAdapter($request, null);

        self::assertTrue($adapter->hasRequestParam('test'));
        self::assertEquals(['test' => 'first'], $adapter->getRequestParams());

        $request = $request->withParsedBody((object) ['test' => 'first']);
        $adapter = new PsrServerRequestAdapter($request, null);

        self::assertTrue($adapter->hasRequestParam('test'));
        self::assertEquals(['test' => 'first'], $adapter->getRequestParams());

        $request = $request->withParsedBody((object) ['test' => 'first', 'test2' => 'second']);
        $adapter = new PsrServerRequestAdapter($request, null);
        self::assertEquals(['test' => 'first', 'test2' => 'second'], $adapter->getRequestParams());
    }

    public function testGetQueryParamsShouldThrowIfParamIsNonExistent(): void
    {
        $request = new ServerRequest('GET', '/', ['Content-Type' => 'application/json']);
        $adapter = new PsrServerRequestAdapter($request, null);

        $this->expectException(NonExistentParameterException::class);
        $this->expectExceptionCode(0);
        $this->expectErrorMessage('You have requested non-existent parameter "test"');

        $adapter->getQueryParam('test');
    }

    public function testGetQueryParamsShouldReturnTheRequestedQueryParameter(): void
    {
        $request = (new ServerRequest('GET', '/'))->withQueryParams(['test' => '42']);
        $adapter = new PsrServerRequestAdapter($request, null);

        self::assertSame('42', $adapter->getQueryParam('test'));
    }

    public function testGetFileShouldThrowIfFileIsNonExistent(): void
    {
        $request = new ServerRequest('GET', '/', ['Content-Type' => 'application/json']);
        $adapter = new PsrServerRequestAdapter($request, null);

        $this->expectException(NonExistentFileException::class);
        $this->expectExceptionCode(0);
        $this->expectErrorMessage('You have requested non-existent file "test"');

        $adapter->getFile('test');
    }

    public function testGetFileShouldReturnTheRequestedFile(): void
    {
        $request = (new ServerRequest('GET', '/'))->withUploadedFiles([
            'test' => new UploadedFile('TEST', 5, UPLOAD_ERR_OK),
        ]);
        $adapter = new PsrServerRequestAdapter($request, null);

        self::assertInstanceOf(UploadedFileInterface::class, $adapter->getFile('test'));
    }

    public function testGetContentType(): void
    {
        $request = new ServerRequest('GET', '/', ['Content-Type' => 'application/json']);
        $adapter = new PsrServerRequestAdapter($request, null);

        self::assertEquals('application/json', $adapter->getContentType());
    }

    public function testGetRequestBody(): void
    {
        $request = (new ServerRequest('GET', '/', ['Content-Type' => 'application/json']))
            ->withBody(Stream::create('{}'));
        $adapter = new PsrServerRequestAdapter($request, null);

        self::assertEquals('{}', $adapter->getRequestContent());
    }

    public function testGetUploadFileErrorShouldThrowOnUnsupportedObjects(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PsrServerRequestAdapter::getUploadFileError(new stdClass());
    }
}
