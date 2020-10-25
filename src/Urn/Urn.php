<?php

declare(strict_types=1);

namespace Solido\Common\Urn;

use InvalidArgumentException;

use function array_shift;
use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function Safe\preg_match;
use function Safe\sprintf;

class Urn
{
    public static string $defaultDomain = '';

    public string $id;
    public string $domain;
    public ?string $partition;
    public ?string $tenant;
    public ?string $owner;
    public ?string $class;

    /**
     * @param mixed $idOrUrn
     * @param string|UrnGeneratorInterface $owner
     */
    public function __construct($idOrUrn, ?string $class = null, $owner = null, ?string $tenant = null, ?string $partition = null, ?string $domain = null)
    {
        if ($idOrUrn instanceof self) {
            $this->id = $idOrUrn->id;
            $this->class = $idOrUrn->class;
            $this->owner = $idOrUrn->owner;
            $this->tenant = $idOrUrn->tenant;
            $this->partition = $idOrUrn->partition;
            $this->domain = $idOrUrn->domain;

            return;
        }

        if ($owner !== null) {
            if ($owner instanceof UrnGeneratorInterface) {
                $owner = (string) $owner->getUrn()->id;
            }

            if (! is_string($owner)) {
                // @phpstan-ignore-next-line
                throw new InvalidArgumentException(sprintf('Owner argument must be an instance of %s or string, %s given', UrnGeneratorInterface::class, is_object($owner) ? get_class($owner) : gettype($owner)));
            }
        }

        if (self::isUrn($idOrUrn)) {
            [$domain, $partition, $tenant, $owner, $class, $idOrUrn] = self::parseUrn($idOrUrn);
        }

        $this->id = $idOrUrn;
        $this->class = $class;
        $this->owner = $owner;
        $this->tenant = $tenant;
        $this->partition = $partition;
        $this->domain = $domain ?? static::$defaultDomain;
    }

    public function __toString(): string
    {
        return sprintf(
            'urn:%s:%s:%s:%s:%s:%s',
            $this->domain,
            $this->partition,
            $this->tenant,
            $this->owner,
            $this->class,
            $this->id
        );
    }

    /**
     * Whether the given argument is an Urn or not.
     *
     * @param mixed $idOrUrn
     */
    public static function isUrn($idOrUrn): bool
    {
        if ($idOrUrn instanceof self) {
            return true;
        }

        if (! is_string($idOrUrn)) {
            return false;
        }

        return (bool) preg_match('/^urn:.*:.*:.*:.*:.*:.*$/', $idOrUrn);
    }

    /**
     * Parse an urn.
     *
     * @param mixed $idOrUrn
     *
     * @return string[]
     */
    private static function parseUrn($idOrUrn): array
    {
        $idOrUrn = (string) $idOrUrn;
        if (! preg_match('/^urn:(.*):(.*):(.*):(.*):(.*):(.*)$/', $idOrUrn, $matches)) {
            throw new InvalidArgumentException('Not an urn');
        }

        array_shift($matches);

        return $matches;
    }
}
