<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Breakouts;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

/**
 * Power Setting (PWR, 0x01), two bytes: the internal rail enables. Defaults (0x07, 0x00) from the reference bring-up.
 */
readonly class JD79661PowerSetting
{
    public function __construct(
        public int $byte0 = 0x07,
        public int $byte1 = 0x00,
    ) {
        foreach (['byte0' => $byte0, 'byte1' => $byte1] as $field => $value) {
            if ($value < 0 || $value > 0xFF) {
                throw JD79661Exception::invalidRegisterValue($field, $value, 0, 0xFF);
            }
        }
    }

    /** @return list<int> */
    public function toBytes(): array
    {
        return [$this->byte0, $this->byte1];
    }

    /** @param  list<int>  $bytes */
    public static function fromBytes(array $bytes): static
    {
        return new static(...array_values($bytes));
    }
}
