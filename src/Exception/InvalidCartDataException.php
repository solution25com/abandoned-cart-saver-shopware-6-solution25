<?php

declare(strict_types=1);

namespace Admin\Exception;

use InvalidArgumentException;

class InvalidCartDataException extends InvalidArgumentException
{
    public function __construct(string $key, string $expectedValue, $actualValue)
    {
        $actualValueType = gettype($actualValue);

        if ($actualValueType === 'object') {
            $actualValueType = get_class($actualValue);
        }

        parent::__construct(
            "Unexpected value for '$key': expecting '$expectedValue', got '$actualValueType'."
        );
    }
}
