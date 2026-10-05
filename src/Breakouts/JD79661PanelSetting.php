<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Breakouts;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

/**
 * Panel Setting (PSR, 0x00), two bytes: resolution select, scan direction, booster and waveform options. The
 * defaults (0x8F, 0x29) are the 2.13 inch 122 × 250 module's, as Adafruit's tested driver writes them.
 */
readonly class JD79661PanelSetting
{
    public function __construct(
        public int $byte0 = 0x8F,
        public int $byte1 = 0x29,
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
