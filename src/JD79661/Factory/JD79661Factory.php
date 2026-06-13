<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Factory;

use BareMetal\CircuitFactory;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Adapters\JD79661SPIAdapter;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661BoosterSoftStart;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PanelSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PLLControl;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PowerOffSequence;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PowerSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661TCON;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661VcomDataInterval;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\JD79661;
use Exception;
use Waveforms\Carriers\GPIO\Factory\GPIOConnectionBuilder;
use Waveforms\Carriers\GPIO\GPIOPin;
use Waveforms\Carriers\SPI\Enums\SPIMode;
use Waveforms\Carriers\SPI\Factory\SPIConnectionBuilder;

class JD79661Factory extends CircuitFactory
{
    protected bool $has_dc = false;

    protected bool $has_rst = false;

    protected bool $has_busy = false;

    protected int $width = 122;

    protected int $height = 250;

    protected int $max_packet_size = 1024;

    public string $consumer = 'jd79661';

    public JD79661PanelSetting $panel_setting;

    public JD79661PowerSetting $power_setting;

    public JD79661PowerOffSequence $power_off_sequence;

    public JD79661BoosterSoftStart $booster_soft_start;

    public JD79661VcomDataInterval $vcom_data_interval;

    public JD79661TCON $tcon;

    public JD79661PLLControl $pll_control;

    public ?SPIConnectionBuilder $connection = null;

    public function __construct(
        public SPIConnectionBuilder $spi_connection,
        public GPIOConnectionBuilder $gpio_connection
    ) {
        $this->panel_setting = new JD79661PanelSetting;
        $this->power_setting = new JD79661PowerSetting;
        $this->power_off_sequence = new JD79661PowerOffSequence;
        $this->booster_soft_start = new JD79661BoosterSoftStart;
        $this->vcom_data_interval = new JD79661VcomDataInterval;
        $this->tcon = new JD79661TCON;
        $this->pll_control = new JD79661PLLControl;
    }

    public function spi(string|int $master, int $chip_select): static
    {
        $this->connection = $this->spi_connection->firstly($master)
            ->chip($chip_select)
            ->speed(25000000)
            ->mode(SPIMode::MODE_0);

        return $this;
    }

    public function gpiochip(int|string $chip): static
    {
        $this->gpio_connection = $this->gpio_connection->firstly($chip);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function dc(int $pin): static
    {
        if (! $this->has_dc) {
            $gpio_output = GPIOPin::createOutput($this->connection->connection(), $pin, 'dc');
            $this->gpio_connection = $this->gpio_connection->addOutput($gpio_output);
            $this->has_dc = true;
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function rst(int $pin): static
    {
        if (! $this->has_rst) {
            $gpio_output = GPIOPin::createOutput($this->connection->connection(), $pin, 'rst');
            $this->gpio_connection = $this->gpio_connection->addOutput($gpio_output);
            $this->has_rst = true;
        }

        return $this;
    }

    public function busy(int $pin, bool $nonblocking = false): static
    {
        if (! $this->has_busy) {
            $gpio_input = GPIOPin::createInput($this->connection->connection(), $pin, 'busy')
                ->edgeEvents();

            if ($nonblocking) {
                $gpio_input = $gpio_input->nonblocking();
            }

            $this->gpio_connection = $this->gpio_connection->addInput($gpio_input);
            $this->has_busy = true;
        }

        return $this;
    }

    public function consumer(string $consumer): static
    {
        $this->consumer = $consumer;

        return $this;
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function maxPacketSize(int $max_packet_size): static
    {
        $this->max_packet_size = $max_packet_size;

        return $this;
    }

    public function panelSetting(JD79661PanelSetting $setting): static
    {
        $this->panel_setting = $setting;

        return $this;
    }

    public function powerSetting(JD79661PowerSetting $setting): static
    {
        $this->power_setting = $setting;

        return $this;
    }

    public function powerOffSequence(JD79661PowerOffSequence $sequence): static
    {
        $this->power_off_sequence = $sequence;

        return $this;
    }

    public function boosterSoftStart(JD79661BoosterSoftStart $booster): static
    {
        $this->booster_soft_start = $booster;

        return $this;
    }

    public function vcomDataInterval(JD79661VcomDataInterval $interval): static
    {
        $this->vcom_data_interval = $interval;

        return $this;
    }

    public function tcon(JD79661TCON $tcon): static
    {
        $this->tcon = $tcon;

        return $this;
    }

    public function pllControl(JD79661PLLControl $pll): static
    {
        $this->pll_control = $pll;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function create(): JD79661
    {
        $carrier = $this->connection?->boot();
        if (is_null($carrier)) {
            throw new Exception('A connection was not registered.');
        }

        $gpio = $this->gpio_connection
            ->shareConnectionWith($carrier)
            ->consumer($this->consumer)
            ->boot();

        $carrier = new JD79661SPIAdapter($carrier, $gpio, $this->max_packet_size, $this->has_busy);

        return new JD79661(
            $carrier,
            $this->width,
            $this->height,
            $this->panel_setting,
            $this->power_setting,
            $this->power_off_sequence,
            $this->booster_soft_start,
            $this->vcom_data_interval,
            $this->tcon,
            $this->pll_control,
        );
    }
}
