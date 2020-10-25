<?php

declare(strict_types=1);

namespace Solido\Common\Urn;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Solido\Common\Exception\InvalidConfigurationException;
use Solido\Common\Exception\ResourceNotFoundException;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\Config\Resource\ResourceInterface;

use function assert;
use function mb_strtolower;
use function Safe\preg_replace;
use function Safe\sprintf;
use function var_export;

class UrnConverter implements UrnConverterInterface
{
    /** @var ManagerRegistry[] */
    private array $managerRegistries;
    private ConfigCacheFactoryInterface $configCache;
    private string $cacheDir;

    /**
     * @param ManagerRegistry[] $managerRegistries
     */
    public function __construct(array $managerRegistries, ConfigCacheFactoryInterface $configCache, string $cacheDir)
    {
        $this->managerRegistries = $managerRegistries;
        $this->configCache = $configCache;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Gets the urn class to entity map.
     *
     * @internal
     *
     * @return string[]
     */
    public function getUrnClassMap(?string $cacheDir = null): array
    {
        $cacheDir ??= $this->cacheDir;
        $cache = $this->configCache->cache($cacheDir . '/urn/class_to_object.php', function (ConfigCacheInterface $cache) {
            $resources = [];
            $map = [];

            foreach ($this->managerRegistries as $registry) {
                $this->processRegistry($registry, $resources, $map);
            }

            $cache->write('<?php return ' . var_export($map, true) . ';', $resources);
        });

        return require $cache->getPath();
    }

    public function getItemFromUrn(Urn $value, ?string $acceptable = null): object
    {
        $map = $this->getUrnClassMap();
        $class = $map[$value->class] ?? null;

        if ($class === null) {
            throw new ResourceNotFoundException('Invalid class ' . $value->class);
        }

        $result = $this->findManager($class)->find($class, $value->id);
        if ($result === null || ($acceptable !== null && ! $result instanceof $acceptable)) {
            throw new ResourceNotFoundException('Cannot find item with urn ' . $value);
        }

        return $result;
    }

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
                $reflectionClass = $classMetadata->getReflectionClass();

                if ($reflectionClass->hasMethod('getUrnClass')) {
                    $method = $reflectionClass->getMethod('getUrnClass');
                    $class = $method->invoke(null);
                } else {
                    $class = mb_strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $reflectionClass->getShortName()));
                }

                if (isset($map[$class])) {
                    throw new InvalidConfigurationException(sprintf('Urn class "%s" is used more than once.', $class));
                }

                $map[$class] = $reflectionClass->getName();
                $resources[] = new ReflectionClassResource($reflectionClass);
            }
        }
    }
}
