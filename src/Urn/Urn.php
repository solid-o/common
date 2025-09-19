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
use function sprintf;

class Urn implements Stringable
{
    public static string $defaultDomain = '';

    public string $id;
    public string $domain;
    public string|null $partition; // phpcs:ignore SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion.RequiredConstructorPropertyPromotion
    public string|null $tenant; // phpcs:ignore SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion.RequiredConstructorPropertyPromotion
    public string|null $owner;
    public string|null $class; // phpcs:ignore SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion.RequiredConstructorPropertyPromotion

    public function __construct(
        mixed $idOrUrn,
        string|null $class = null,
        string|Stringable|UrnGeneratorInterface|null $owner = null,
        string|null $tenant = null,
        string|null $partition = null,
        string|null $domain = null,
    ) {
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

        $this->id = (string) $idOrUrn; // @phpstan-ignore-line
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
            $this->id,
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Whether the given argument is an Urn or not.
     */
    public static function isUrn(mixed $idOrUrn): bool
    {
        if ($idOrUrn instanceof self) {
            return true;
        }

        if (! is_string($idOrUrn)) {
            return false;
        }

        return (bool) preg_match('/^urn:.*:.*:.*:.*:.*:\S*$/', $idOrUrn);
    }

    /**
     * Parse an urn.
     *
     * @return array<string|null>
     */
    private static function parseUrn(mixed $idOrUrn): array
    {
        $idOrUrn = (string) $idOrUrn; // @phpstan-ignore-line

        $result = preg_match('/^urn:(.*):(.*):(.*):(.*):(.*):(\S*)$/', $idOrUrn, $matches);
        assert($result === 1 && $matches !== null);

        array_shift($matches);

        return array_map(static fn (string $value): string|null => empty($value) ? null : $value, $matches);
    }
}
