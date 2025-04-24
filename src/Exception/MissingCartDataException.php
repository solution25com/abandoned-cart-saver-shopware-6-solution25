<?php

declare(strict_types=1);

namespace Admin\Exception;

use LogicException;


class MissingCartDataException extends LogicException
{
    public function __construct(string $requiredValue)
    {
        parent::__construct("Required value for '$requiredValue' is missing.");
    }
}
