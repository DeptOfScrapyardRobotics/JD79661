<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Concerns;

use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661BoosterSoftStart;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PanelSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PLLControl;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PowerOffSequence;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PowerSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661TCON;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661VcomDataInterval;
use DeptOfScrapyardRobotics\Displays\JD79661\Enums\JD79661DeepSleepCheck;
use DeptOfScrapyardRobotics\Displays\JD79661\Enums\JD79661OpCode;
use DeptOfScrapyardRobotics\Displays\JD79661\Enums\JD79661VendorRegister;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Configuration;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;
use DeptOfScrapyardRobotics\Displays\JD79661\Transports\JD79661DataTransport;

/** Register setters: each writes the chip, then the configuration. */
trait JD79661API
{
    abstract public function config(): JD79661Configuration;

    abstract public function transport(): JD79661DataTransport;

    protected function sendCommand(JD79661OpCode $register, array $command_data = []): int
    {
        return $this->transport()->command($register->value, $command_data);
    }

    /** One vendor register with its fixed value. */
    public function writeVendorRegister(JD79661VendorRegister $register): void
    {
        $this->transport()->command($register->value, [$register->payload()]);
    }

    /** @throws JD79661Exception when BUSY stays low past busy_timeout_ms */
    public function waitUntilIdle(): void
    {
        $this->transport()->waitUntilIdle($this->config()->get('busy_timeout_ms'));
    }

    public function setPanelSetting(JD79661PanelSetting $setting): void
    {
        $this->sendCommand(JD79661OpCode::PANEL_SETTING, $setting->toBytes());
        $this->config()->set('panel_setting', $setting);
    }

    public function setPowerSetting(JD79661PowerSetting $setting): void
    {
        $this->sendCommand(JD79661OpCode::POWER_SETTING, $setting->toBytes());
        $this->config()->set('power_setting', $setting);
    }

    public function setPowerOffSequence(JD79661PowerOffSequence $sequence): void
    {
        $this->sendCommand(JD79661OpCode::POWER_OFF_SEQUENCE, $sequence->toBytes());
        $this->config()->set('power_off_sequence', $sequence);
    }

    public function setBoosterSoftStart(JD79661BoosterSoftStart $booster): void
    {
        $this->sendCommand(JD79661OpCode::BOOSTER_SOFT_START, $booster->toBytes());
        $this->config()->set('booster_soft_start', $booster);
    }

    public function setVcomDataInterval(JD79661VcomDataInterval $interval): void
    {
        $this->sendCommand(JD79661OpCode::VCOM_DATA_INTERVAL, $interval->toBytes());
        $this->config()->set('vcom_data_interval', $interval);
    }

    public function setTCON(JD79661TCON $tcon): void
    {
        $this->sendCommand(JD79661OpCode::TCON, $tcon->toBytes());
        $this->config()->set('tcon', $tcon);
    }

    public function setPLLControl(JD79661PLLControl $pll): void
    {
        $this->sendCommand(JD79661OpCode::PLL_CONTROL, $pll->toBytes());
        $this->config()->set('pll_control', $pll);
    }

    /** Resolution (TRES, 0x61): sources rounded up to a byte of pixels, then gates, each 16-bit big-endian. */
    public function setResolution(int $width, int $height): void
    {
        $sources = intdiv($width + 7, 8) * 8;

        $this->sendCommand(JD79661OpCode::RESOLUTION_SETTING, [($sources >> 8) & 0xFF, $sources & 0xFF, ($height >> 8) & 0xFF, $height & 0xFF]);
    }

    public function powerOn(): void
    {
        $this->sendCommand(JD79661OpCode::POWER_ON);
        $this->waitUntilIdle();
    }

    public function powerOff(): void
    {
        $this->sendCommand(JD79661OpCode::POWER_OFF, [0x00]);
        $this->waitUntilIdle();
    }

    /**
     * Power off, then deep sleep. The panel keeps showing its image; only a hardware reset wakes the controller,
     * so the next boot() runs the whole sequence again.
     */
    public function sleep(): void
    {
        $this->powerOff();
        $this->sendCommand(JD79661OpCode::DEEP_SLEEP, [JD79661DeepSleepCheck::CODE->value]);
        $this->booted = false;
    }
}
