<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Transports;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DataCommander;

/**
 * Commands and frame bytes to the panel, its reset line, and its BUSY line (low while busy). Every write is
 * checked. SPI has no acknowledge, so a missing panel shows up as BUSY never rising, not as a write error.
 */
abstract class JD79661DataTransport implements DataCommander
{
    public function __construct(
        protected int $max_packet_size
    ) {
        $this->maxPacketSize($max_packet_size);
    }

    abstract protected function sendData(array|string $data = []): void;
    abstract protected function sendCommand(int $register, array $command_data = []): int;

    /** Pulse the hardware reset line. */
    abstract public function reset(): void;

    /** True while the controller holds BUSY low. */
    abstract public function busy(): bool;

    abstract public function close(): void;

    public function command(int $register, array $command_data = []): int
    {
        return $this->sendCommand($register, $command_data);
    }

    public function data(array|string $data = []): void
    {
        $this->sendData($data);
    }

    /** Bytes per data write; SPI adapters split a longer write into the bus's own message size. */
    public function maxPacketSize(int $size): static
    {
        if ($size < 1) {
            throw JD79661Exception::invalidPacketSize($size);
        }

        $this->max_packet_size = $size;

        return $this;
    }

    /**
     * Block until the controller releases BUSY, reading it every millisecond.
     *
     * @throws JD79661Exception when BUSY stays low for $timeout_ms
     */
    public function waitUntilIdle(int $timeout_ms): void
    {
        $deadline = hrtime(true) + $timeout_ms * 1_000_000;

        while ($this->busy()) {
            if (hrtime(true) >= $deadline) {
                throw JD79661Exception::busyTimeout($timeout_ms);
            }

            usleep(1_000);
        }
    }

    /** @throws JD79661Exception when the bus wrote fewer bytes than asked */
    protected function checked(string $what, int $expected, int $written): int
    {
        if ($written !== $expected) {
            throw JD79661Exception::spiWriteFailed($what, $expected, $written);
        }

        return $written;
    }
}
