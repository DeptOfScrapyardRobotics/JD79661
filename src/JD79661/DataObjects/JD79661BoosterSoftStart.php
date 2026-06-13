<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions\JD79661Exception;

/**
 * Booster Soft Start — BTST_P (0x06), 7 parameter bytes.
 *
 * Programs the soft-start ramp of the on-chip charge-pump boosters (drive
 * strength, minimum off-time, phase periods). The defaults match the JD79661
 * reference bring-up:
 *   0x05, 0x00, 0x3F, 0x0A, 0x25, 0x12, 0x1A
 */
readonly class JD79661BoosterSoftStart
{
    /**
     * @var list<int>
     */
    public array $bytes;

    public function __construct(int ...$bytes)
    {
        if ($bytes === []) {
            $bytes = [0x05, 0x00, 0x3F, 0x0A, 0x25, 0x12, 0x1A];
        }

        if (count($bytes) !== 7) {
            throw JD79661Exception::invalidRegisterValue('booster_byte_count', count($bytes), 7, 7);
        }

        foreach ($bytes as $index => $value) {
            if (($value < 0) || ($value > 0xFF)) {
                throw JD79661Exception::invalidRegisterValue("byte{$index}", $value, 0, 0xFF);
            }
        }

        $this->bytes = array_values($bytes);
    }

    /**
     * @return list<int>
     */
    public function toBytes(): array
    {
        return $this->bytes;
    }
}
