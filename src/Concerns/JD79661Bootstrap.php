<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Concerns;

use DeptOfScrapyardRobotics\Displays\JD79661\Enums\JD79661VendorRegister;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

trait JD79661Bootstrap
{
    use JD79661API;

    /**
     * Any configuration key reads as a property.
     *
     * @throws JD79661Exception
     */
    public function __get(string $name): mixed
    {
        return $this->config()->get($name);
    }

    /**
     * @throws JD79661Exception
     */
    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'panel_setting' => $this->setPanelSetting($value),
            'power_setting' => $this->setPowerSetting($value),
            'power_off_sequence' => $this->setPowerOffSequence($value),
            'booster_soft_start' => $this->setBoosterSoftStart($value),
            'vcom_data_interval' => $this->setVcomDataInterval($value),
            'tcon' => $this->setTCON($value),
            'pll_control' => $this->setPLLControl($value),
            default => throw JD79661Exception::invalidProperty($name, static::class),
        };
    }

    /**
     * The reference bring-up, in its order: reset, wait, the magic-init unlock, panel / power / booster / timing
     * registers, resolution, the vendor registers, PLL, power on.
     */
    protected function _boot(): void
    {
        $config = $this->config();

        $this->transport()->maxPacketSize($config->get('max_packet_size'));
        $this->transport()->reset();
        $this->waitUntilIdle();
        usleep(10_000);

        $this->writeVendorRegister(JD79661VendorRegister::MAGIC_INIT);
        $this->setPanelSetting($config->get('panel_setting'));
        $this->setPowerSetting($config->get('power_setting'));
        $this->setPowerOffSequence($config->get('power_off_sequence'));
        $this->setBoosterSoftStart($config->get('booster_soft_start'));
        $this->setVcomDataInterval($config->get('vcom_data_interval'));
        $this->setTCON($config->get('tcon'));
        $this->setResolution($this->width(), $this->height());
        $this->writeVendorRegister(JD79661VendorRegister::CONFIG_E7);
        $this->writeVendorRegister(JD79661VendorRegister::POWER_SAVING);
        $this->writeVendorRegister(JD79661VendorRegister::CONFIG_B4);
        $this->writeVendorRegister(JD79661VendorRegister::CONFIG_B5);
        $this->writeVendorRegister(JD79661VendorRegister::CONFIG_E9);
        $this->setPLLControl($config->get('pll_control'));
        $this->powerOn();
    }
}
