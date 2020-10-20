<?php

declare(strict_types=1);

namespace Solido\Common\Urn;

interface UrnConverterInterface
{
    /**
     * Gets an item from its urn.
     * If not found an.
     */
    public function getItemFromUrn(Urn $value, ?string $acceptable = null): object;
}
