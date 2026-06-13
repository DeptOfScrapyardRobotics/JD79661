<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions;

use RuntimeException;

class JD79661Exception extends RuntimeException
{
    public static function invalidProperty(string $name): static
    {
        return new static("Invalid property $name");
    }

    public static function invalidRegisterValue(string $field, int $value, int $min, int $max): static
    {
        return new static("Valid $field values are between $min and $max, you input $value.");
    }
}
