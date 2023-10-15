<?php

declare(strict_types=1);

namespace Solido\Common\Exception;

use RuntimeException;
use Throwable;

use function sprintf;

class NonExistentFileException extends RuntimeException
{
    public function __construct(string $fileName, Throwable|null $previous = null)
    {
        parent::__construct(sprintf('You have requested non-existent file "%s"', $fileName), 0, $previous);
    }
}
