<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions\JD79661Exception;

/**
 * Power Off Sequence Setting — POFS (0x03), 3 parameter bytes.
 *
 * Controls the gate/source power-down timing applied when the panel powers
 * off. The defaults (0x10, 0x54, 0x44) match the JD79661 reference bring-up.
 */
readonly class JD79661PowerOffSequence
{
    public function __construct(
        public int $byte0 = 0x10,
        public int $byte1 = 0x54,
        public int $byte2 = 0x44,
    ) {
        $this->assertByte($this->byte0, 'byte0');
        $this->assertByte($this->byte1, 'byte1');
        $this->assertByte($this->byte2, 'byte2');
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
            $this->byte0 & 0xFF,
            $this->byte1 & 0xFF,
            $this->byte2 & 0xFF,
        ];
    }

    public static function fromBytes(int $byte0 = 0x10, int $byte1 = 0x54, int $byte2 = 0x44): static
    {
        return new static($byte0, $byte1, $byte2);
    }
}
