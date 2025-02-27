<?php

declare(strict_types=1);

namespace Solido\Common\Urn;

use Doctrine\ORM\Mapping\ClassMetadata as ORMMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Solido\Common\Exception\InvalidConfigurationException;
use Solido\Common\Exception\ResourceNotFoundException;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\Config\Resource\ResourceInterface;

use function assert;
use function in_array;
use function is_string;
use function preg_replace;
use function sprintf;
use function strtolower;
use function var_export;

class UrnConverter implements UrnConverterInterface
{
    /** @var string[]|null */
    private array|null $urnDomains = null;

    /** @param ManagerRegistry[] $managerRegistries */
    public function __construct(
        private readonly array $managerRegistries,
        private readonly ConfigCacheFactoryInterface $configCache,
        private readonly string $cacheDir,
    ) {
    }

    public function setDomains(string ...$domains): void
    {
        $this->urnDomains = $domains;
    }

    /**
     * Gets the urn class to entity map.
     *
     * @internal
     *
     * @return string[]
     * @phpstan-return class-string[]
     */
    public function getUrnClassMap(string|null $cacheDir = null): array
    {
        if (empty($cacheDir)) {
            $cacheDir = $this->cacheDir;
        }

        $cache = $this->configCache->cache($cacheDir . '/urn/class_to_object.php', function (ConfigCacheInterface $cache): void {
            $resources = [];
            $map = [];

            foreach ($this->managerRegistries as $registry) {
                $this->processRegistry($registry, $resources, $map);
            }

            $cache->write('<?php return ' . var_export($map, true) . ';', $resources);
        });

        return require $cache->getPath();
    }

    public function getItemFromUrn(Urn $value, string|null $acceptable = null): object
    {
        if ($this->urnDomains && ! in_array($value->domain, $this->urnDomains, true)) {
            throw new ResourceNotFoundException(sprintf('Invalid domain "%s"', $value->domain));
        }

        $map = $this->getUrnClassMap();

        /** @phpstan-var class-string|null $class */
        $class = $map[$value->class] ?? null;
        if ($class === null) {
            throw new ResourceNotFoundException(sprintf('Invalid class "%s"', $value->class));
        }

        $result = $this->findManager($class)->find($class, $value->id);
        if ($result === null || ($acceptable !== null && ! $result instanceof $acceptable)) {
            throw new ResourceNotFoundException(sprintf('Cannot find item with urn "%s"', $value));
        }

        return $result;
    }

    /** @param class-string $class */
    private function findManager(string $class): ObjectManager
    {
        $om = null;
        foreach ($this->managerRegistries as $registry) {
            $manager = $registry->getManagerForClass($class);
            if ($manager !== null) {
                $om = $manager;
                break;
            }
        }

        assert($om instanceof ObjectManager);

        return $om;
    }

    /**
     * @param ResourceInterface[] $resources
     * @param array<string, string> $map
     */
    private function processRegistry(ManagerRegistry $managerRegistry, array &$resources, array &$map): void
    {
        $oms = $managerRegistry->getManagers();

        foreach ($oms as $objectManager) {
            $metadata = $objectManager->getMetadataFactory()->getAllMetadata();

            foreach ($metadata as $classMetadata) {
                if (
                    $classMetadata instanceof ORMMetadata &&
                    $classMetadata->isInheritanceTypeSingleTable() &&
                    $classMetadata->rootEntityName !== $classMetadata->name
                ) {
                    continue;
                }

                $reflectionClass = $classMetadata->getReflectionClass();
                if (! $reflectionClass->implementsInterface(UrnGeneratorInterface::class)) {
                    continue;
                }

                if ($reflectionClass->hasMethod('getUrnClass')) {
                    $method = $reflectionClass->getMethod('getUrnClass');
                    $class = $method->invoke(null);
                } else {
                    $snakeCase = preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $reflectionClass->getShortName());
                    assert(is_string($snakeCase));

                    $class = strtolower($snakeCase);
                }

                assert(is_string($class));
                if (isset($map[$class])) {
                    throw new InvalidConfigurationException(sprintf('Urn class "%s" is used more than once.', $class));
                }

                $map[$class] = $reflectionClass->getName();
                $resources[] = new ReflectionClassResource($reflectionClass);
            }
        }
    }
}
