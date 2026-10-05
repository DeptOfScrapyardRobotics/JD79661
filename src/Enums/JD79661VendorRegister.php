<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Enums;

/**
 * The vendor-tuned registers the reference bring-up writes (manufacturer demo and Adafruit's driver agree), with
 * the one value each takes. Their meaning is not published. MAGIC_INIT comes first after reset: the panel takes no
 * other setting before it.
 */
enum JD79661VendorRegister: int
{
    case MAGIC_INIT = 0x4D;
    case CONFIG_B4 = 0xB4;
    case CONFIG_B5 = 0xB5;
    case POWER_SAVING = 0xE3;
    case CONFIG_E7 = 0xE7;
    case CONFIG_E9 = 0xE9;

    public function payload(): int
    {
        return match ($this) {
            self::MAGIC_INIT => 0x78,
            self::CONFIG_B4 => 0xD0,
            self::CONFIG_B5 => 0x03,
            self::POWER_SAVING => 0x22,
            self::CONFIG_E7 => 0x1C,
            self::CONFIG_E9 => 0x01,
        };
    }
}
