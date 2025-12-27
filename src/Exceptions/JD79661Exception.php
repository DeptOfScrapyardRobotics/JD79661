<?php

namespace ScrapyardIO\Displays\ePaper\JD79661\Exceptions;

use ScrapyardIO\Support\Exceptions\ScrapyardIOException;

class JD79661Exception extends ScrapyardIOException
{
    public static function invalidProtocol(string $name): static
    {
        return new static("Unsupported protocol '{$name}'.");
    }

    public static function pixelOutOfBounds(int $x): static
    {
        return new static("$x not a valid pixel index");
    }
}
