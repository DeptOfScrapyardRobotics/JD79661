<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Adapters;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Enums\JD79661OpCode;
use Waveforms\Carriers\GPIO\GPIOBus;
use Waveforms\Carriers\SPI\SPIDevice;

class JD79661SPIAdapter extends JD79661DataCarrier
{
    public function __construct(
        SPIDevice $carrier,
        protected GPIOBus $gpio,
        protected int $max_packet_size,
        protected bool $has_busy = false,
    ) {
        parent::__construct($carrier);
    }

    public function reset(): void
    {
        $this->gpio->rst()->high();
        usleep(200000);

        $this->gpio->rst()->low();
        usleep(10000);

        $this->gpio->rst()->high();
        usleep(200000);
    }

    public function data(array $data): void
    {
        foreach (array_chunk($data, $this->max_packet_size) as $chunk) {
            $this->gpio->dc()->high();
            $this->carrier->write($chunk);
        }
    }

    public function command(JD79661OpCode $register_hex, array $command_data = []): void
    {
        $this->gpio->dc()->low();
        $this->carrier->write([$register_hex->value]);

        if (count($command_data) > 0) {
            $this->data($command_data);
        }
    }

    public function waitUntilIdle(int $timeout_us = 10000000): void
    {
        if (! $this->has_busy) {
            usleep(100000);

            return;
        }

        $interval_us = 10000;
        $waited_us = 0;

        while ($this->gpio->busy()->read() === 0) {
            usleep($interval_us);
            $waited_us += $interval_us;

            if ($waited_us >= $timeout_us) {
                break;
            }
        }
    }
}
