<?php

declare(strict_types=1);

namespace Solido\Common\Tests\Urn;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Solido\Common\Urn\Urn;
use Solido\Common\Urn\UrnGeneratorInterface;
use stdClass;

class UrnTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Urn::$defaultDomain = 'test-domain';
    }

    public static function tearDownAfterClass(): void
    {
        Urn::$defaultDomain = '';
    }

    public function provideIsUrnData(): iterable
    {
        yield [true, 'urn:custom_domain:123:::class-name:my-id'];
        yield [true, 'urn:domain::::class-name:my-id'];
        yield [false, 'not-an-urn:domain::::class-name:my-id'];
        yield [false, 'not-an-urn'];
        yield [false, new stdClass()];
        yield [true, new Urn('not-an-urn', 'class')];
    }

    /**
     * @dataProvider provideIsUrnData
     */
    public function testIsUrnShouldWork(bool $expected, $value): void
    {
        self::assertEquals($expected, Urn::isUrn($value));
    }

    public function testCouldBeConstructed(): void
    {
        $urn = new Urn('urn:custom_domain:123:::class-name:my-id');
        self::assertEquals('my-id', $urn->id);
        self::assertEquals('class-name', $urn->class);
        self::assertEquals('custom_domain', $urn->domain);
        self::assertEquals('123', $urn->partition);

        $urn = new Urn('my-id', 'class');
        self::assertEquals('my-id', $urn->id);
        self::assertEquals('test-domain', $urn->domain);

        $urn = new Urn($urn);
        self::assertEquals('my-id', $urn->id);
        self::assertEquals('test-domain', $urn->domain);

        $urn = new Urn('my-id', 'class-name', new class implements UrnGeneratorInterface {
            public function getUrn(): Urn
            {
                return new Urn('urn:custom_domain:123:::class-name:owner-id');
            }
        });
        self::assertEquals('my-id', $urn->id);
        self::assertEquals('class-name', $urn->class);
        self::assertEquals('owner-id', $urn->owner);
    }

    public function testShouldThrowIfOwnerIsObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Urn('my-id', 'class-name', new \stdClass());
    }
}
