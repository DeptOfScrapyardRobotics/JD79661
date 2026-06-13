<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions\JD79661Exception;

/**
 * Power Setting — PWR (0x01), 2 parameter bytes.
 *
 * Configures the internal power rails / regulator enables. The defaults
 * (0x07, 0x00) match the JD79661 reference bring-up.
 */
readonly class JD79661PowerSetting
{
    public function __construct(
        public int $byte0 = 0x07,
        public int $byte1 = 0x00,
    ) {
        $this->assertByte($this->byte0, 'byte0');
        $this->assertByte($this->byte1, 'byte1');
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
        ];
    }

    public static function fromBytes(int $byte0 = 0x07, int $byte1 = 0x00): static
    {
        return new static($byte0, $byte1);
    }
}
