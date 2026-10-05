<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661;

use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661BoosterSoftStart;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PanelSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PLLControl;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PowerOffSequence;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PowerSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661TCON;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661VcomDataInterval;

/**
 * The panel's geometry and the values boot writes; the defaults are the 2.13 inch 122 × 250 module's reference
 * bring-up. width runs along the sources, height along the gates.
 */
class JD79661Configuration
{
    protected JD79661PanelSetting $panel_setting;

    protected JD79661PowerSetting $power_setting;

    protected JD79661PowerOffSequence $power_off_sequence;

    protected JD79661BoosterSoftStart $booster_soft_start;

    protected JD79661VcomDataInterval $vcom_data_interval;

    protected JD79661TCON $tcon;

    protected JD79661PLLControl $pll_control;

    /**
     * @param  int  $busy_timeout_ms  longest BUSY may stay low before a command or refresh fails; a full refresh
     *                                takes about 20 s
     */
    public function __construct(
        protected int $width = 122,
        protected int $height = 250,
        ?JD79661PanelSetting $panel_setting = null,
        ?JD79661PowerSetting $power_setting = null,
        ?JD79661PowerOffSequence $power_off_sequence = null,
        ?JD79661BoosterSoftStart $booster_soft_start = null,
        ?JD79661VcomDataInterval $vcom_data_interval = null,
        ?JD79661TCON $tcon = null,
        ?JD79661PLLControl $pll_control = null,
        protected int $max_packet_size = 4096,
        protected int $busy_timeout_ms = 60_000,
    ) {
        if ($width < 1 || $width > 65528 || $height < 1 || $height > 65535) {
            throw JD79661Exception::invalidGeometry($width, $height);
        }

        if ($busy_timeout_ms < 1) {
            throw JD79661Exception::invalidRegisterValue('busy_timeout_ms', $busy_timeout_ms, 1, PHP_INT_MAX);
        }

        $this->panel_setting = $panel_setting ?? new JD79661PanelSetting;
        $this->power_setting = $power_setting ?? new JD79661PowerSetting;
        $this->power_off_sequence = $power_off_sequence ?? new JD79661PowerOffSequence;
        $this->booster_soft_start = $booster_soft_start ?? new JD79661BoosterSoftStart;
        $this->vcom_data_interval = $vcom_data_interval ?? new JD79661VcomDataInterval;
        $this->tcon = $tcon ?? new JD79661TCON;
        $this->pll_control = $pll_control ?? new JD79661PLLControl;
    }

    public function get(string $var): mixed
    {
        if (isset($this->$var)) {
            return $this->$var;
        }

        throw JD79661Exception::invalidProperty($var, static::class);
    }

    public function set(string $var, mixed $value): void
    {
        if (isset($this->$var)) {
            $this->$var = $value;

            return;
        }

        throw JD79661Exception::invalidProperty($var, static::class);
    }
}
