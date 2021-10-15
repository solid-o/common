<?php

declare(strict_types=1);

namespace Solido\Common\Urn;

interface UrnConverterInterface
{
    /**
     * Gets an item from its urn.
     * If not found an exception will be thrown.
     *
     * @param class-string<T> | null $acceptable
     *
     * @return T | object
     *
     * @template T of object
     */
    public function getItemFromUrn(Urn $value, ?string $acceptable = null): object;
}
