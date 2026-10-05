<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Breakouts;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

/**
 * Power Off Sequence (POFS, 0x03), three bytes: gate and source power-down timing. Defaults (0x10, 0x54, 0x44)
 * from the reference bring-up.
 */
readonly class JD79661PowerOffSequence
{
    public function __construct(
        public int $byte0 = 0x10,
        public int $byte1 = 0x54,
        public int $byte2 = 0x44,
    ) {
        foreach (['byte0' => $byte0, 'byte1' => $byte1, 'byte2' => $byte2] as $field => $value) {
            if ($value < 0 || $value > 0xFF) {
                throw JD79661Exception::invalidRegisterValue($field, $value, 0, 0xFF);
            }
        }
    }

    /** @return list<int> */
    public function toBytes(): array
    {
        return [$this->byte0, $this->byte1, $this->byte2];
    }

    /** @param  list<int>  $bytes */
    public static function fromBytes(array $bytes): static
    {
        return new static(...array_values($bytes));
    }
}
