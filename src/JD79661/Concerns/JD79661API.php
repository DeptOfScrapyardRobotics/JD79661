<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Concerns;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661BoosterSoftStart;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PanelSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PLLControl;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PowerOffSequence;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PowerSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661TCON;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661VcomDataInterval;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Enums\JD79661OpCode;

trait JD79661API
{
    use JD79661InternalAPI;

    /**
     * Vendor magic-init register (0x4D -> 0x78). MUST be the first command
     * after reset; the panel rejects all documented settings until it lands.
     */
    public function magicInit(): void
    {
        $this->vendor(JD79661OpCode::MAGIC_INIT);
    }

    public function setPanelSetting(JD79661PanelSetting $setting): void
    {
        $this->command(JD79661OpCode::PANEL_SETTING, $setting->toBytes());
    }

    public function setPowerSetting(JD79661PowerSetting $setting): void
    {
        $this->command(JD79661OpCode::POWER_SETTING, $setting->toBytes());
    }

    public function setPowerOffSequence(JD79661PowerOffSequence $sequence): void
    {
        $this->command(JD79661OpCode::POWER_OFF_SEQUENCE, $sequence->toBytes());
    }

    public function setBoosterSoftStart(JD79661BoosterSoftStart $booster): void
    {
        $this->command(JD79661OpCode::BOOSTER_SOFT_START, $booster->toBytes());
    }

    public function setVcomDataInterval(JD79661VcomDataInterval $interval): void
    {
        $this->command(JD79661OpCode::VCOM_DATA_INTERVAL, $interval->toBytes());
    }

    public function setTCON(JD79661TCON $tcon): void
    {
        $this->command(JD79661OpCode::TCON, $tcon->toBytes());
    }

    public function setResolution(int $source_bits, int $gate_bits): void
    {
        $this->command(JD79661OpCode::RESOLUTION_SETTING, [
            ($source_bits >> 8) & 0xFF,
            $source_bits & 0xFF,
            ($gate_bits >> 8) & 0xFF,
            $gate_bits & 0xFF,
        ]);
    }

    public function setPowerSaving(): void
    {
        $this->vendor(JD79661OpCode::POWER_SAVING);
    }

    public function setPLLControl(JD79661PLLControl $pll): void
    {
        $this->command(JD79661OpCode::PLL_CONTROL, $pll->toBytes());
    }

    public function powerOn(): void
    {
        $this->command(JD79661OpCode::POWER_ON);
        $this->waitUntilIdle();
    }

    public function powerOff(): void
    {
        $this->command(JD79661OpCode::POWER_OFF);
        $this->waitUntilIdle();
    }

    /**
     * Drop the controller into deep sleep (~uA). The 0xA5 check code is
     * mandatory; a hardware reset is required to wake the panel afterward.
     */
    public function deepSleep(): void
    {
        $this->command(JD79661OpCode::DEEP_SLEEP, [0xA5]);
    }

    /**
     * Stream a full 2bpp frame into the panel's single color RAM.
     *
     * @param  array<int, int>  $bytes
     */
    public function writeFrame(array $bytes): void
    {
        $this->command(JD79661OpCode::DATA_START_TRANSMISSION, $bytes);
    }

    /**
     * Fire a full-panel refresh from RAM and block until the panel finishes.
     * The 0x00 data byte after DRF is required by the reference bring-up.
     */
    public function refresh(): void
    {
        $this->command(JD79661OpCode::DISPLAY_REFRESH, [0x00]);
        $this->waitUntilIdle();
    }
}
