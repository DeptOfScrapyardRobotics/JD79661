<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Enums;

enum JD79661OpCode: int
{
    // Magic Init Register (MUST be sent first!)
    case MAGIC_INIT = 0x4D;

    // Core Display Commands
    case PANEL_SETTING = 0x00;          // PSR - Panel Setting Register
    case POWER_SETTING = 0x01;          // PWR - Power Setting
    case POWER_OFF = 0x02;              // POF - Power Off
    case POWER_OFF_SEQUENCE = 0x03;     // POFS - Power Off Sequence
    case POWER_ON = 0x04;               // PON - Power On
    case BOOSTER_SOFT_START = 0x06;     // BTST/BTST_P - Booster Soft Start
    case DEEP_SLEEP = 0x07;             // DSLP - Deep Sleep

    // Data Transfer Commands
    case DATA_START_TRANSMISSION = 0x10; // DTM - Data Start Transmission
    case DISPLAY_REFRESH = 0x12;         // DRF - Display Refresh

    // Configuration Commands
    case PLL_CONTROL = 0x30;            // PLL - PLL Control
    case VCOM_DATA_INTERVAL = 0x50;     // CDI - VCOM and Data Interval Setting
    case TCON = 0x60;                   // TCON - Temperature Sensor Control
    case RESOLUTION_SETTING = 0x61;     // TRES - Resolution Setting
    case PARTIAL_WINDOW = 0x83;         // PTL - Partial Window

    // Extended/Proprietary Commands
    case CONFIG_B4 = 0xB4;              // Undocumented config register
    case CONFIG_B5 = 0xB5;              // Undocumented config register
    case POWER_SAVING = 0xE3;           // PWS - Power Saving
    case CONFIG_E7 = 0xE7;              // Undocumented config register
    case CONFIG_E9 = 0xE9;              // Undocumented config register

    /**
     * Fixed manufacturer payload for the undocumented / vendor-tuned init
     * registers, reproduced verbatim from the JD79661 reference bring-up
     * (manufacturer demo + Adafruit's tested driver agree on these). Nobody
     * has published what most of them do; the silicon insists on them.
     *
     * The magic-init register (0x4D) MUST be clocked out first, before the
     * panel will accept any of the documented power/panel settings.
     *
     * @return list<int> Data bytes to clock out after the opcode, or an empty
     *                   list for documented commands handled elsewhere.
     */
    public function vendorPayload(): array
    {
        return match ($this) {
            self::MAGIC_INIT => [0x78],
            self::POWER_SAVING => [0x22],
            self::CONFIG_B4 => [0xD0],
            self::CONFIG_B5 => [0x03],
            self::CONFIG_E7 => [0x1C],
            self::CONFIG_E9 => [0x01],
            default => [],
        };
    }
}
