<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Breakouts;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

/**
 * Booster Soft Start (BTST, 0x06), seven bytes: the charge-pump soft-start ramp. Defaults
 * (0x05, 0x00, 0x3F, 0x0A, 0x25, 0x12, 0x1A) from the reference bring-up.
 */
readonly class JD79661BoosterSoftStart
{
    /** @var list<int> */
    public array $bytes;

    public function __construct(int ...$bytes)
    {
        $bytes = $bytes === [] ? [0x05, 0x00, 0x3F, 0x0A, 0x25, 0x12, 0x1A] : array_values($bytes);

        if (count($bytes) !== 7) {
            throw JD79661Exception::invalidRegisterValue('booster byte count', count($bytes), 7, 7);
        }

        foreach ($bytes as $index => $value) {
            if ($value < 0 || $value > 0xFF) {
                throw JD79661Exception::invalidRegisterValue("booster byte {$index}", $value, 0, 0xFF);
            }
        }

        $this->bytes = $bytes;
    }

    /** @return list<int> */
    public function toBytes(): array
    {
        return $this->bytes;
    }
}
