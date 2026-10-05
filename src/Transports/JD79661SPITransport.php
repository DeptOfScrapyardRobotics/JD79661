<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Transports;

use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use GeneralPurposeIO\Contracts\SPI\SPITransport;

/** 4-wire SPI: DC low for the command byte, high for its parameters and frame data; RST and BUSY (active low). */
class JD79661SPITransport extends JD79661DataTransport
{
    public function __construct(
        protected SPITransport $transport,
        protected DigitalOutTransport $dc,
        protected DigitalOutTransport $rst,
        protected DigitalInTransport $busy,
        int $max_packet_size = 4096,
    ) {
        parent::__construct($max_packet_size);
    }

    protected function sendCommand(int $register, array $command_data = []): int
    {
        $this->dc->low();
        $written = $this->checked(sprintf('command 0x%02X', $register), 1, $this->transport->write([$register]));

        if (count($command_data) > 0) {
            $this->sendData($command_data);
        }

        return $written;
    }

    protected function sendData(array|string $data = []): void
    {
        $this->dc->high();

        if (is_string($data)) {
            foreach (str_split($data, $this->max_packet_size) as $chunk) {
                $this->checked('data', strlen($chunk), $this->transport->write($chunk));
            }

            return;
        }

        foreach (array_chunk($data, $this->max_packet_size) as $chunk) {
            $this->checked('data', count($chunk), $this->transport->write($chunk));
        }
    }

    /** RES# high 20 ms, low 40 ms, high 50 ms, as Adafruit's driver drives it. */
    public function reset(): void
    {
        $this->rst->high();
        usleep(20_000);

        $this->rst->low();
        usleep(40_000);

        $this->rst->high();
        usleep(50_000);
    }

    public function busy(): bool
    {
        return ! $this->busy->read();
    }

    /** Release DC, RST and BUSY. The bus connection belongs to its driver and stays open. */
    public function close(): void
    {
        $this->dc->close();
        $this->rst->close();
        $this->busy->close();
    }
}
