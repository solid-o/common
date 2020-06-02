<?php

declare(strict_types=1);

namespace Solido\Common\Urn;

/**
 * Represents a resource capabale of generating its own URN.
 */
interface UrnGeneratorInterface
{
    /**
     * Gets the URN (Uniform Resource Name) for this object.
     *
     * By convention the URN should be generated as follow:
     *
     *  urn:domain:partition:tenant:owner_id:resource_class:id
     *
     * If not present, segments could be omitted. Ex:
     *
     *  urn:domain::::resource_class:id
     */
    public function getUrn(): Urn;
}
