<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Breakouts;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

/**
 * TCON (0x60), two bytes: source-to-gate and gate-to-source non-overlap. Defaults (0x02, 0x02) from the reference
 * bring-up.
 */
readonly class JD79661TCON
{
    public function __construct(
        public int $s2g = 0x02,
        public int $g2s = 0x02,
    ) {
        foreach (['s2g' => $s2g, 'g2s' => $g2s] as $field => $value) {
            if ($value < 0 || $value > 0xFF) {
                throw JD79661Exception::invalidRegisterValue($field, $value, 0, 0xFF);
            }
        }
    }

    /** @return list<int> */
    public function toBytes(): array
    {
        return [$this->s2g, $this->g2s];
    }

    /** @param  list<int>  $bytes */
    public static function fromBytes(array $bytes): static
    {
        return new static(...array_values($bytes));
    }
}
