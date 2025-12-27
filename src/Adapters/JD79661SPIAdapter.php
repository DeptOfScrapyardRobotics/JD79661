<?php

namespace ScrapyardIO\Displays\ePaper\JD79661\Adapters;

use ScrapyardIO\Displays\ePaper\JD79661\Concerns\JD79661SPIChip;
use ScrapyardIO\Displays\ePaper\JD79661\Concerns\JD79661BootSequence;
use ScrapyardIO\Displays\Adapters\TwoBitQuadColorEPaperDisplayAdapter;

class JD79661SPIAdapter extends TwoBitQuadColorEPaperDisplayAdapter
{
    use JD79661SPIChip;
    use JD79661BootSequence;

    public function bus(int $bus):static
    {
        $this->spi_jd79661_bus($bus);
        return $this;
    }

    public function chipSelect(int $cs):static
    {
        $this->spi_jd79661_chip_select($cs);
        return $this;
    }

    public function dcPin(int $chip, int $line): static
    {
        $this->dc_chip($chip);
        $this->dc_line($line);
        $this->dc_gpio();

        return $this;
    }

    public function rstPin(int $chip, int $line): static
    {
        $this->rst_chip($chip);
        $this->rst_line($line);
        $this->rst_gpio();

        return $this;
    }

    public function busyPin(int $chip, int $line): static
    {
        $this->busy_chip($chip);
        $this->busy_line($line);
        $this->busy_gpio();

        return $this;
    }

    public function boot(): static
    {
        $this->jd79661_spi();

        $this->resetSequence();
        $this->setMagicInit();
        $this->setPanelSetting();
        $this->setPowerSetting();
        $this->setPowerOffSequence();
        $this->setBoosterSoftStart();
        $this->setVcomDataInterval();
        $this->setTcon();
        $this->setResolution();
        $this->setExtendedConfig();
        $this->setPllControl();
        $this->powerOn();
        $this->busyWait(25000);

        return $this;
    }
}
