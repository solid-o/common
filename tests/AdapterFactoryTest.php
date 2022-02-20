<?php

declare(strict_types=1);

namespace Solido\Common\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile as PsrUploadedFile;
use PHPUnit\Framework\TestCase;
use Solido\Common\AdapterFactory;
use Solido\Common\Exception\InvalidArgumentException;
use Solido\Common\Exception\UnsupportedRequestObjectException;
use Solido\Common\Exception\UnsupportedResponseObjectException;
use Solido\Common\RequestAdapter\PsrServerRequestAdapter;
use Solido\Common\RequestAdapter\SymfonyHttpFoundationRequestAdapter;
use Solido\Common\ResponseAdapter\PsrResponseAdapter;
use Solido\Common\ResponseAdapter\SymfonyHttpFoundationResponseAdapter;
use stdClass;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\HttpFoundation\Response as SfResponse;

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_OK;

class AdapterFactoryTest extends TestCase
{
    public function testCreateRequestAdapterForSfHttpFoundationRequest(): void
    {
        $factory = new AdapterFactory();
        $request = new SfRequest();

        $adapter = $factory->createRequestAdapter($request);
        self::assertInstanceOf(SymfonyHttpFoundationRequestAdapter::class, $adapter);
    }

    public function testCreateRequestAdapterForPsr7Request(): void
    {
        $factory = new AdapterFactory();
        $request = new ServerRequest('GET', '/');

        $adapter = $factory->createRequestAdapter($request);
        self::assertInstanceOf(PsrServerRequestAdapter::class, $adapter);

        $this->expectException(InvalidArgumentException::class);
        $adapter->createResponse();
    }

    public function testCreateRequestAdapterForPsr7RequestWithResponseFactory(): void
    {
        $factory = new AdapterFactory(new Psr17Factory());
        $request = new ServerRequest('GET', '/');

        $adapter = $factory->createRequestAdapter($request);
        self::assertInstanceOf(PsrServerRequestAdapter::class, $adapter);
        self::assertInstanceOf(PsrResponseAdapter::class, $adapter->createResponse());
    }

    public function testCreateRequestAdapterShouldThrowOnUnknownRequestObject(): void
    {
        $this->expectException(UnsupportedRequestObjectException::class);
        $factory = new AdapterFactory();
        $factory->createRequestAdapter(new stdClass());
    }

    public function testCreateResponseAdapterForSfHttpFoundationRequest(): void
    {
        $factory = new AdapterFactory();
        $response = new SfResponse();

        $adapter = $factory->createResponseAdapter($response);
        self::assertInstanceOf(SymfonyHttpFoundationResponseAdapter::class, $adapter);
    }

    public function testCreateResponseAdapterForPsr7Response(): void
    {
        $factory = new AdapterFactory();
        $response = new Response(200);

        $adapter = $factory->createResponseAdapter($response);
        self::assertInstanceOf(PsrResponseAdapter::class, $adapter);
    }

    public function testCreateRequestAdapterShouldThrowOnUnknownResponseObject(): void
    {
        $this->expectException(UnsupportedResponseObjectException::class);
        $factory = new AdapterFactory();
        $factory->createResponseAdapter(new stdClass());
    }

    public function testUploadFileErrorShouldReturnTheError(): void
    {
        $file = new UploadedFile(__FILE__, 'script.php', 'text/plain', UPLOAD_ERR_OK, true);
        $factory = new AdapterFactory();
        self::assertNull($factory->getUploadFileError($file));

        $file = new UploadedFile(__FILE__, 'script.php', 'text/plain', UPLOAD_ERR_CANT_WRITE, true);
        $factory = new AdapterFactory();
        self::assertEquals(UPLOAD_ERR_CANT_WRITE, $factory->getUploadFileError($file));

        $file = new PsrUploadedFile('TEST', 5, UPLOAD_ERR_OK);
        self::assertNull($factory->getUploadFileError($file));

        $file = new PsrUploadedFile('TEST', 5, UPLOAD_ERR_INI_SIZE);
        self::assertEquals(UPLOAD_ERR_INI_SIZE, $factory->getUploadFileError($file));
    }

    public function testUploadFileErrorShouldReturnNullOnUnknownClass(): void
    {
        $factory = new AdapterFactory();
        self::assertNull($factory->getUploadFileError(new stdClass()));
    }

    public function testUploadFileErrorShouldReturnCorrectFileUploadErrorCode(): void
    {
        $factory = new AdapterFactory();

        $file = new UploadedFile(__FILE__, 'xyz', 'text/plain', UPLOAD_ERR_INI_SIZE, true);
        self::assertEquals(UPLOAD_ERR_INI_SIZE, $factory->getUploadFileError($file));

        $file = new UploadedFile(__FILE__, 'xyz', 'text/plain', UPLOAD_ERR_OK, true);
        self::assertNull($factory->getUploadFileError($file));

        $file = new File(__FILE__, true);
        self::assertNull($factory->getUploadFileError($file));
    }

    /**
     * @dataProvider provideFileUpload
     */
    public function testIsFileUpload(bool $expected, $value): void
    {
        $factory = new AdapterFactory();
        self::assertEquals($expected, $factory->isFileUpload($value));
    }

    public function provideFileUpload(): iterable
    {
        yield [true, new File(__FILE__)];
        yield [true, new UploadedFile(__FILE__, 'xyz', 'text/plain', UPLOAD_ERR_OK, true)];
        yield [true, new PsrUploadedFile(__FILE__, filesize(__FILE__), UPLOAD_ERR_OK)];
        yield [false, new stdClass()];
        yield [false, []];
        yield [false, 'foobar'];
    }
}
