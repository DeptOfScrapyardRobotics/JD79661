<?php

namespace ScrapyardIO\Displays\ePaper\JD79661\Concerns;

use ScrapyardIO\Transports\SPITransport;
use ScrapyardIO\Transports\Concerns\BusyPin;
use ScrapyardIO\Transports\Concerns\ResetPin;
use ScrapyardIO\Transports\Concerns\DataCommandPin;

trait JD79661SPIChip
{
    use BusyPin, DataCommandPin, ResetPin;

    protected ?SPITransport $jd79661_spi = null;
    protected int $jd79661_spi_bus = 0;
    protected int $spi_jd79661_chip_select = 0;
    protected int $max_packet_size = 4096;

    abstract public function wait(int $ms): void;

    protected function spi_jd79661_bus(?int $bus = null): int
    {
        if(!is_null($bus))
        {
            $this->jd79661_spi_bus = $bus;
        }
        return $this->jd79661_spi_bus;
    }

    protected function spi_jd79661_chip_select(?int $cs = null): int
    {
        if($cs)
        {
            $this->spi_jd79661_chip_select = $cs;
        }
        return $this->spi_jd79661_chip_select;
    }

    protected function jd79661_spi(): ?SPITransport
    {
        if(empty($this->jd79661_spi))
        {
            $this->jd79661_spi = new SPITransport(
                $this->spi_jd79661_bus(),
                $this->spi_jd79661_chip_select(),
                0,
                25000000,
                0
            );
        }

        return $this->jd79661_spi;
    }

    public function sendData(array $bytes): void
    {
        $this->dcHigh();
        $this->jd79661_spi()->send($bytes);
    }

    public function sendCommand(array $bytes): void
    {
        $this->dcLow();
        if(count($bytes) > 1)
        {
            $command = $bytes[0];
            $this->jd79661_spi()->send([$command]);
            unset($bytes[0]);
            $payload = array_values($bytes);
            $this->sendData($payload);
        }
        else
        {
            $this->jd79661_spi()->send($bytes);
        }
    }

    protected function resetSequence(): void
    {
        $this->rstHigh();
        $this->wait(20);

        $this->rstLow();
        $this->wait(40);

        $this->rstHigh();
        $this->wait(50);

        $this->dcLow();
    }

    public function isBusy(): bool
    {
        return $this->busy_gpio()->read() === 0;
    }
}
