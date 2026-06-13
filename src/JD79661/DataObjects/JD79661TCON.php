<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions\JD79661Exception;

/**
 * Timing Controller — TCON (0x60), 2 parameter bytes.
 *
 * Sets the source-to-gate and gate-to-source non-overlap timing. The defaults
 * (0x02, 0x02) match the JD79661 reference bring-up.
 */
readonly class JD79661TCON
{
    public function __construct(
        public int $s2g = 0x02,
        public int $g2s = 0x02,
    ) {
        $this->assertByte($this->s2g, 's2g');
        $this->assertByte($this->g2s, 'g2s');
    }

    private function assertByte(int $value, string $field): void
    {
        if (($value < 0) || ($value > 0xFF)) {
            throw JD79661Exception::invalidRegisterValue($field, $value, 0, 0xFF);
        }
    }

    /**
     * @return list<int>
     */
    public function toBytes(): array
    {
        return [
            $this->s2g & 0xFF,
            $this->g2s & 0xFF,
        ];
    }

    public static function fromBytes(int $s2g = 0x02, int $g2s = 0x02): static
    {
        return new static($s2g, $g2s);
    }
}
