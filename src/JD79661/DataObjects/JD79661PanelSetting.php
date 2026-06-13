<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions\JD79661Exception;

/**
 * Panel Setting Register — PSR (0x00), 2 parameter bytes.
 *
 * Selects the resolution decode, colour mode, gate/source scan direction and
 * a handful of waveform options for this 4-colour (black/white/red/yellow)
 * panel. The defaults (0x0F, 0x29) match the JD79661 manufacturer reference
 * bring-up for the 2.13" 122x250 module.
 */
readonly class JD79661PanelSetting
{
    public function __construct(
        public int $byte0 = 0x0F,
        public int $byte1 = 0x29,
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

    public static function fromBytes(int $byte0 = 0x0F, int $byte1 = 0x29): static
    {
        return new static($byte0, $byte1);
    }
}
