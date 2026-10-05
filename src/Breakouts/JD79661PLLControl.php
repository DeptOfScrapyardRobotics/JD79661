<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Breakouts;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

/**
 * PLL Control (0x30), one byte: the frame rate. Default 0x08 from the reference bring-up.
 */
readonly class JD79661PLLControl
{
    public function __construct(
        public int $frame_rate = 0x08,
    ) {
        foreach (['frame_rate' => $frame_rate] as $field => $value) {
            if ($value < 0 || $value > 0xFF) {
                throw JD79661Exception::invalidRegisterValue($field, $value, 0, 0xFF);
            }
        }
    }

    /** @return list<int> */
    public function toBytes(): array
    {
        return [$this->frame_rate];
    }

    /** @param  list<int>  $bytes */
    public static function fromBytes(array $bytes): static
    {
        return new static(...array_values($bytes));
    }
}
