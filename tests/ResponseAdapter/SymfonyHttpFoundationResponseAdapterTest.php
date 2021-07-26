<?php

declare(strict_types=1);

namespace Solido\Common\Tests\ResponseAdapter;

use PHPUnit\Framework\TestCase;
use Solido\Common\ResponseAdapter\SymfonyHttpFoundationResponseAdapter;
use Stringable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function fopen;
use function fpassthru;
use function fseek;
use function fwrite;

class SymfonyHttpFoundationResponseAdapterTest extends TestCase
{
    public function testGetContentType(): void
    {
        $response = new Response(null, 200, ['Content-Type' => 'application/json']);
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        self::assertEquals('application/json', $adapter->getContentType());

        $response = new Response(null, 200, [
            'Content-Type' => new class implements Stringable {
                public function __toString()
                {
                    return 'text/xml';
                }
            },
        ]);
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        self::assertEquals('text/xml', $adapter->getContentType());
    }

    public function testGetContentTypeShouldDefaultToOctetStream(): void
    {
        $response = new Response();
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        self::assertEquals('application/octet-stream', $adapter->getContentType());
    }

    public function testSetHeaders(): void
    {
        $response = new Response();
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        $adapter->setHeaders([
            'Content-Type' => 'application/json',
            'X-Another' => 'header',
        ]);

        $headers = $adapter->unwrap()->headers->all();

        self::assertArrayHasKey('content-type', $headers);
        self::assertEquals(['application/json'], $headers['content-type']);
        self::assertArrayHasKey('x-another', $headers);
        self::assertEquals(['header'], $headers['x-another']);
    }

    public function testGetContentWithStream(): void
    {
        $stream = fopen('php://temp', 'wb');
        fwrite($stream, 'BODY');
        fseek($stream, 0);

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
        });
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        self::assertEquals('BODY', $adapter->getContent());
    }

    public function testGetContentWithBinaryFileResponse(): void
    {
        $stream = fopen('php://temp', 'wb');
        fwrite($stream, 'BODY');
        fseek($stream, 0);

        $response = new BinaryFileResponse(__FILE__);
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        self::assertStringEqualsFile(__FILE__, $adapter->getContent());
    }

    public function testGetContent(): void
    {
        $response = new Response('BODY');
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        self::assertEquals('BODY', $adapter->getContent());
    }

    public function testGetHeader(): void
    {
        $response = new Response();
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        $adapter->setHeaders([
            'Content-Type' => 'application/json',
            'X-Another' => 'header',
            'X-User' => new class implements Stringable {
                public function __toString()
                {
                    return 'user-header';
                }
            },
        ]);

        self::assertEquals(['header'], $adapter->getHeader('x-another'));
        self::assertEquals(['user-header'], $adapter->getHeader('x-user'));
        self::assertEquals([], $adapter->getHeader('x-another-2'));
    }

    public function testGetHeaders(): void
    {
        $response = new Response();
        $response->headers->replace([
            'Content-Type' => 'application/json',
            'X-Another' => 'header',
        ]);

        $adapter = new SymfonyHttpFoundationResponseAdapter($response);
        $headers = $adapter->getHeaders();

        self::assertArrayHasKey('content-type', $headers);
        self::assertEquals(['application/json'], $headers['content-type']);
        self::assertArrayHasKey('x-another', $headers);
        self::assertEquals(['header'], $headers['x-another']);
    }

    public function testGetStatusCode(): void
    {
        $response = new Response();
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        self::assertEquals(200, $adapter->getStatusCode());

        $response = new Response(null, 499);
        $adapter = new SymfonyHttpFoundationResponseAdapter($response);

        self::assertEquals(499, $adapter->getStatusCode());
    }
}
