<?php

declare(strict_types=1);

namespace Solido\Common\Tests\Urn;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
use Solido\Common\Exception\InvalidConfigurationException;
use Solido\Common\Exception\ResourceNotFoundException;
use Solido\Common\Urn\Urn;
use Solido\Common\Urn\UrnConverter;
use Solido\Common\Urn\UrnGeneratorInterface;
use Symfony\Component\Config\ConfigCacheFactory;

use function mkdir;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class UrnConverterTest extends TestCase
{
    use ProphecyTrait;

    /** @var ManagerRegistry|ObjectProphecy */
    private object $managerRegistry;

    private string $cacheDir;
    private UrnConverter $converter;

    protected function setUp(): void
    {
        $this->cacheDir = tempnam(sys_get_temp_dir(), 'solido-common');
        @unlink($this->cacheDir);
        @mkdir($this->cacheDir);

        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->converter = new UrnConverter([$this->managerRegistry->reveal()], new ConfigCacheFactory(true), $this->cacheDir);
        $this->converter->setDomains('example-application');
    }

    public function testGetItemFromUrnShouldCallObjectManagerFind(): void
    {
        $this->managerRegistry->getManagers()->willReturn([
            $manager = $this->prophesize(ObjectManager::class),
        ]);
        $this->managerRegistry->getManagerForClass(TestEntity::class)->willReturn($manager);

        $manager->find(TestEntity::class, 'test-42')
            ->shouldBeCalledOnce()
            ->willReturn(new TestEntity());

        $manager->getMetadataFactory()->willReturn($factory = $this->prophesize(AbstractClassMetadataFactory::class));
        $factory->getAllMetadata()->willReturn([
            $metadata = new ClassMetadata(TestEntity::class),
        ]);

        $metadata->wakeupReflection(new RuntimeReflectionService());

        $this->converter->getItemFromUrn(new Urn('test-42', 'user', null, null, null, 'example-application'));
    }

    public function testGetItemFromUrnShouldThrowIfNoMatchingClassIsFound(): void
    {
        $this->managerRegistry->getManagers()->willReturn([
            $manager = $this->prophesize(ObjectManager::class),
        ]);
        $this->managerRegistry->getManagerForClass(TestEntity::class)->willReturn($manager);

        $manager->find(Argument::cetera())->shouldNotBeCalled();
        $manager->getMetadataFactory()->willReturn($factory = $this->prophesize(AbstractClassMetadataFactory::class));
        $factory->getAllMetadata()->willReturn([
            $metadata = new ClassMetadata(TestEntity::class),
        ]);

        $metadata->wakeupReflection(new RuntimeReflectionService());

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Invalid class "not-a-user"');

        $this->converter->getItemFromUrn(new Urn('test-42', 'not-a-user', null, null, null, 'example-application'));
    }

    public function testGetItemFromUrnShouldThrowIfInvalidDomainIsPassed(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Invalid domain "invalid-domain"');

        $this->converter->getItemFromUrn(new Urn('test-42', 'not-a-user', null, null, null, 'invalid-domain'));
    }

    public function testGetItemFromUrnShouldThrowIfNoAcceptableClassIsFound(): void
    {
        $this->managerRegistry->getManagers()->willReturn([
            $manager = $this->prophesize(ObjectManager::class),
        ]);
        $this->managerRegistry->getManagerForClass(TestEntity::class)->willReturn($manager);

        $manager->find(TestEntity::class, 'test-42')
                ->shouldBeCalledOnce()
                ->willReturn(new TestEntity());

        $manager->getMetadataFactory()->willReturn($factory = $this->prophesize(AbstractClassMetadataFactory::class));
        $factory->getAllMetadata()->willReturn([
            $metadata = new ClassMetadata(TestEntity::class),
        ]);

        $metadata->wakeupReflection(new RuntimeReflectionService());

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Cannot find item with urn "urn:example-application::::user:test-42"');

        $this->converter->getItemFromUrn(new Urn('test-42', 'user', null, null, null, 'example-application'), self::class);
    }

    public function testGetItemFromUrnShouldNotThrowIfAcceptableClassIsFound(): void
    {
        $this->managerRegistry->getManagers()->willReturn([
            $manager = $this->prophesize(ObjectManager::class),
        ]);
        $this->managerRegistry->getManagerForClass(TestEntity::class)->willReturn($manager);

        $manager->find(TestEntity::class, 'test-42')
                ->shouldBeCalledOnce()
                ->willReturn(new TestEntity());

        $manager->getMetadataFactory()->willReturn($factory = $this->prophesize(AbstractClassMetadataFactory::class));
        $factory->getAllMetadata()->willReturn([
            $metadata = new ClassMetadata(TestEntity::class),
        ]);

        $metadata->wakeupReflection(new RuntimeReflectionService());
        $this->converter->getItemFromUrn(new Urn('test-42', 'user', null, null, null, 'example-application'), TestEntity::class);
    }

    public function testGetItemFromUrnShouldThrowIfManagerFindReturnsNull(): void
    {
        $this->managerRegistry->getManagers()->willReturn([
            $manager = $this->prophesize(ObjectManager::class),
        ]);
        $this->managerRegistry->getManagerForClass(TestEntity::class)->willReturn($manager);

        $manager->find(TestEntity::class, 'test-42')
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $manager->getMetadataFactory()->willReturn($factory = $this->prophesize(AbstractClassMetadataFactory::class));
        $factory->getAllMetadata()->willReturn([
            $metadata = new ClassMetadata(TestEntity::class),
        ]);

        $metadata->wakeupReflection(new RuntimeReflectionService());

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Cannot find item with urn "urn:example-application::::user:test-42"');

        $this->converter->getItemFromUrn(new Urn('test-42', 'user', null, null, null, 'example-application'));
    }

    public function testNonUrnGeneratorsAreExcludedFromClassMap(): void
    {
        $this->managerRegistry->getManagers()->willReturn([
            $manager = $this->prophesize(ObjectManager::class),
        ]);

        $this->managerRegistry->getManagerForClass(TestEntity::class)->willReturn($manager);

        $manager->getMetadataFactory()->willReturn($factory = $this->prophesize(AbstractClassMetadataFactory::class));
        $factory->getAllMetadata()->willReturn([
            $metadata = new ClassMetadata(TestEntity::class),
            $metadata2 = new ClassMetadata(TestEntity2::class),
            $metadata3 = new ClassMetadata(TestNonUrnEntity::class),
        ]);

        $metadata->wakeupReflection(new RuntimeReflectionService());
        $metadata2->wakeupReflection(new RuntimeReflectionService());
        $metadata3->wakeupReflection(new RuntimeReflectionService());

        self::assertEquals([
            'user' => TestEntity::class,
            'test_entity2' => TestEntity2::class,
        ], $this->converter->getUrnClassMap());
    }

    public function testShouldThrowIfUrnClassIsUsedMoreThanOnce(): void
    {
        $this->managerRegistry->getManagers()->willReturn([
            $manager = $this->prophesize(ObjectManager::class),
        ]);

        $this->managerRegistry->getManagerForClass(TestEntity::class)->willReturn($manager);

        $manager->getMetadataFactory()->willReturn($factory = $this->prophesize(AbstractClassMetadataFactory::class));
        $factory->getAllMetadata()->willReturn([
            $metadata = new ClassMetadata(TestEntity::class),
            $metadata2 = new ClassMetadata(TestDuplicatedEntity::class),
        ]);

        $metadata->wakeupReflection(new RuntimeReflectionService());
        $metadata2->wakeupReflection(new RuntimeReflectionService());

        $this->expectException(InvalidConfigurationException::class);
        $this->converter->getUrnClassMap();
    }

    public function testShouldSTIChildrenEntitiesAreExcludedFromClassMap(): void
    {
        $this->managerRegistry->getManagers()->willReturn([
            $manager = $this->prophesize(ObjectManager::class),
        ]);

        $this->managerRegistry->getManagerForClass(TestEntity::class)->willReturn($manager);

        $manager->getMetadataFactory()->willReturn($factory = $this->prophesize(AbstractClassMetadataFactory::class));
        $factory->getAllMetadata()->willReturn([
            $metadata3 = new ClassMetadata(TestDuplicatedEntity::class),
            $metadata = new ClassMetadata(TestNonUrnEntity::class),
            $metadata2 = new ClassMetadata(TestEntity::class),
            $invalidMetadata = $this->prophesize(ClassMetadataInterface::class),
        ]);

        $invalidMetadata->getReflectionClass()->willReturn(new ReflectionClass($this));

        $metadata3->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $metadata3->rootEntityName = TestEntity::class;

        $metadata->wakeupReflection(new RuntimeReflectionService());
        $metadata2->wakeupReflection(new RuntimeReflectionService());
        $metadata3->wakeupReflection(new RuntimeReflectionService());

        self::assertEquals([
            'user' => TestEntity::class,
        ], $this->converter->getUrnClassMap());
    }
}

class TestEntity implements UrnGeneratorInterface
{
    public static function getUrnClass(): string
    {
        return 'user';
    }

    public function getUrn(): Urn
    {
    }
}

class TestEntity2 implements UrnGeneratorInterface
{
    public function getUrn(): Urn
    {
    }
}

class TestDuplicatedEntity implements UrnGeneratorInterface
{
    public static function getUrnClass(): string
    {
        return 'user';
    }

    public function getUrn(): Urn
    {
    }
}

class TestNonUrnEntity
{
}
