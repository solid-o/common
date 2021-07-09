<?php

declare(strict_types=1);

namespace Solido\Common\Exception;

use RuntimeException;
use Throwable;

use function Safe\sprintf;

class NonExistentParameterException extends RuntimeException
{
    public function __construct(string $parameter, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('You have requested non-existent parameter "%s"', $parameter), 0, $previous);
    }
}
