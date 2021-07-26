<?php

declare(strict_types=1);

namespace Solido\Common\Tests\ResponseAdapter;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Solido\Common\ResponseAdapter\PsrResponseAdapter;

class PsrResponseAdapterTest extends TestCase
{
    public function testGetContentType(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json']);
        $adapter = new PsrResponseAdapter($response);

        self::assertEquals('application/json', $adapter->getContentType());
    }

    public function testGetContentTypeShouldDefaultToOctetStream(): void
    {
        $response = new Response();
        $adapter = new PsrResponseAdapter($response);

        self::assertEquals('application/octet-stream', $adapter->getContentType());
    }

    public function testSetHeaders(): void
    {
        $response = new Response();
        $adapter = new PsrResponseAdapter($response);

        $adapter->setHeaders([
            'Content-Type' => 'application/json',
            'X-Another' => 'header',
        ]);

        self::assertEquals([
            'Content-Type' => ['application/json'],
            'X-Another' => ['header'],
        ], $adapter->unwrap()->getHeaders());
    }

    public function testGetContent(): void
    {
        $response = (new Response())->withBody(Stream::create('BODY'));
        $adapter = new PsrResponseAdapter($response);

        self::assertEquals('BODY', $adapter->getContent());
    }

    public function testGetHeader(): void
    {
        $response = new Response();
        $adapter = new PsrResponseAdapter($response);

        $adapter->setHeaders([
            'Content-Type' => 'application/json',
            'X-Another' => 'header',
        ]);

        self::assertEquals(['header'], $adapter->getHeader('x-another'));
        self::assertEquals([], $adapter->getHeader('x-another-2'));
    }

    public function testGetHeaders(): void
    {
        $response = (new Response())
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Another', 'header');
        $adapter = new PsrResponseAdapter($response);

        $headers = $adapter->getHeaders();

        self::assertArrayHasKey('content-type', $headers);
        self::assertEquals(['application/json'], $headers['content-type']);
        self::assertArrayHasKey('x-another', $headers);
        self::assertEquals(['header'], $headers['x-another']);
    }

    public function testGetStatusCode(): void
    {
        $response = new Response();
        $adapter = new PsrResponseAdapter($response);

        self::assertEquals(200, $adapter->getStatusCode());

        $response = new Response(499);
        $adapter = new PsrResponseAdapter($response);

        self::assertEquals(499, $adapter->getStatusCode());
    }
}
