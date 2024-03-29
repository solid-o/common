<?php

declare(strict_types=1);

namespace Solido\Common\Urn;

use ProxyManager\Proxy\ProxyInterface;
use ReflectionClass;

use function preg_replace;
use function strtolower;

trait UrnGeneratorTrait
{
    /**
     * Should return the resource partition (or null if not applicable).
     */
    public function getUrnPartition(): string|null
    {
        return null;
    }

    /**
     * Should return the resource tenant (or null if not applicable).
     */
    public function getUrnTenant(): string|null
    {
        return null;
    }

    /**
     * Should return the resource owner identifier (or null if not applicable).
     */
    public function getUrnOwner(): string|null
    {
        return null;
    }

    /**
     * Gets the urn class.
     * Defaults to a snake case version of the class name.
     */
    public static function getUrnClass(): string
    {
        $reflectionClass = new ReflectionClass(static::class);
        if ($reflectionClass->isSubclassOf(ProxyInterface::class)) {
            $reflectionClass = $reflectionClass->getParentClass();
        }

        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $reflectionClass->getShortName()));
    }

    /**
     * Must return the resource identifier (or path) as string.
     */
    abstract public function getUrnId(): string;

    /**
     * Generates an urn object for the current resource.
     */
    public function getUrn(): Urn
    {
        return new Urn($this->getUrnId(), static::getUrnClass(), $this->getUrnOwner(), $this->getUrnTenant(), $this->getUrnPartition());
    }
}
