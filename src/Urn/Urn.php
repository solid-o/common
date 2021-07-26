<?php

declare(strict_types=1);

namespace Solido\Common\Urn;

use Solido\Common\Exception\InvalidArgumentException;
use Stringable;

use function array_map;
use function array_shift;
use function assert;
use function get_debug_type;
use function is_string;
use function Safe\preg_match;
use function Safe\sprintf;

class Urn implements Stringable
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
     * @param string|Stringable|UrnGeneratorInterface $owner
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
                $owner = $owner->getUrn()->id;
            }

            if (! is_string($owner)) {
                throw new InvalidArgumentException(sprintf('Owner argument must be an instance of %s or string, %s given', UrnGeneratorInterface::class, get_debug_type($owner)));
            }
        }

        if (self::isUrn($idOrUrn)) {
            [$domain, $partition, $tenant, $owner, $class, $idOrUrn] = self::parseUrn($idOrUrn);
        }

        if (empty($class)) {
            throw new InvalidArgumentException('URN class must be defined');
        }

        $this->id = $idOrUrn;
        $this->class = $class;
        $this->owner = $owner;
        $this->tenant = $tenant;
        $this->partition = $partition;
        $this->domain = $domain ?? static::$defaultDomain;
    }

    public function toString(): string
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

    public function __toString(): string
    {
        return $this->toString();
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
     * @return array<string|null>
     */
    private static function parseUrn($idOrUrn): array
    {
        $idOrUrn = (string) $idOrUrn;

        $result = preg_match('/^urn:(.*):(.*):(.*):(.*):(.*):(.*)$/', $idOrUrn, $matches);
        assert($result === 1);

        array_shift($matches);

        return array_map(static fn (string $value): ?string => empty($value) ? null : $value, $matches);
    }
}
