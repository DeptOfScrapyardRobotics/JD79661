<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Adapters;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Enums\JD79661OpCode;
use Waveforms\Carriers\SPI\SPIDevice;

abstract class JD79661DataCarrier
{
    public function __construct(
        protected SPIDevice $carrier
    ) {}

    abstract public function data(array $data): void;

    abstract public function command(JD79661OpCode $register_hex, array $command_data = []): void;

    public function reset(): void {}

    public function waitUntilIdle(): void {}
}
