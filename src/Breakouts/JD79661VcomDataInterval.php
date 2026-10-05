<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Breakouts;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

/**
 * VCOM and Data Interval (CDI, 0x50), one byte: VCOM-to-data interval and border polarity. Default 0x37 from the
 * reference bring-up.
 */
readonly class JD79661VcomDataInterval
{
    public function __construct(
        public int $interval = 0x37,
    ) {
        foreach (['interval' => $interval] as $field => $value) {
            if ($value < 0 || $value > 0xFF) {
                throw JD79661Exception::invalidRegisterValue($field, $value, 0, 0xFF);
            }
        }
    }

    /** @return list<int> */
    public function toBytes(): array
    {
        return [$this->interval];
    }

    /** @param  list<int>  $bytes */
    public static function fromBytes(array $bytes): static
    {
        return new static(...array_values($bytes));
    }
}
