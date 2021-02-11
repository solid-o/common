<?php declare(strict_types=1);

namespace Solido\Common\Tests\Urn;

use PHPUnit\Framework\TestCase;
use ProxyManager\Proxy\ProxyInterface;
use Solido\Common\Urn\Urn;
use Solido\Common\Urn\UrnGeneratorTrait;

class UrnGeneratorTraitTest extends TestCase
{
    public function testGetUrnClassShouldReturnedTheSluggedClassName(): void
    {
        self::assertEquals('concrete_urn_generator', ConcreteUrnGenerator::getUrnClass());
        self::assertEquals('concrete_urn_generator', ConcreteUrnGeneratorProxy::getUrnClass());
    }

    public function testDefaultValues(): void
    {
        Urn::$defaultDomain = 'test-domain';

        $generator = new ConcreteUrnGenerator();
        self::assertNull($generator->getUrnPartition());
        self::assertNull($generator->getUrnTenant());
        self::assertNull($generator->getUrnOwner());

        self::assertEquals('urn:test-domain::::concrete_urn_generator:42', $generator->getUrn()->toString());
        self::assertEquals('urn:test-domain::::concrete_urn_generator:42', (string) $generator->getUrn());
    }
}

class ConcreteUrnGenerator
{
    use UrnGeneratorTrait;

    public function getUrnId(): string
    {
        return '42';
    }
}

class ConcreteUrnGeneratorProxy extends ConcreteUrnGenerator implements ProxyInterface
{
}
