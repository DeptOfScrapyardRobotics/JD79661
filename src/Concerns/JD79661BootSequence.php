<?php

namespace ScrapyardIO\Displays\ePaper\JD79661\Concerns;

use ScrapyardIO\Displays\ePaper\JD79661\Enums\JD79661Command;

trait JD79661BootSequence
{
    // Display resolution (buffer size vs visible area)
    protected int $display_width = 128;      // Buffer width (internal - always send 128)
    protected int $display_height = 250;     // Buffer height (internal)
    protected int $visible_width = 122;      // Visible width to report to controller
    protected int $x_offset = 0;             // Horizontal offset

    // Booster soft start timing configuration
    protected array $booster_soft_start = [
        0x05,  // Phase 1: Soft start period
        0x00,  // Phase 2: Driving strength
        0x3F,  // Phase 3: Min off time
        0x0A,  // Phase 4: Driving strength
        0x25,  // Phase 5: VCOM driving strength
        0x12,  // Phase 6: VCOM min off time
        0x1A   // Phase 7: VGH/VGL timing
    ];

    // Power offset sequence timing
    protected array $power_offset_sequence = [
        0x10,  // Phase 1
        0x54,  // Phase 2
        0x44   // Phase 3
    ];

    // VCOM and data interval setting
    protected int $vcom_data_interval = 0x37;  // 0x37 for full refresh, 0x97 for partial

    // TCON (Temperature sensor control)
    protected array $tcon_setting = [
        0x02,  // S2G and G2S timing
        0x02   // Gate timing
    ];

    // Panel setting register values
    protected int $panel_setting_1 = 0x0F;  // Resolution and LUT selection
    protected int $panel_setting_2 = 0x29;  // Scan direction and data polarity

    // Power setting values
    protected int $power_setting_1 = 0x07;  // VGH/VGL levels
    protected int $power_setting_2 = 0x00;  // VSH/VSL levels

    // PLL Control
    protected int $pll_control = 0x08;  // Frame rate control

    // Extended config registers (undocumented but required)
    protected int $config_e7 = 0x1C;
    protected int $config_b4 = 0xD0;
    protected int $config_b5 = 0x03;
    protected int $power_saving = 0x22;
    protected int $config_e9 = 0x01;

    abstract public function wait(int $ms): void;
    abstract public function sendData(array $bytes): void;
    abstract public function sendCommand(array $bytes): void;
    abstract public function busyWait(int $timeout_ms = 0): bool;

    protected function setMagicInit(): void
    {
        $this->sendCommand([
            JD79661Command::MAGIC_INIT->value,
            0x78
        ]);
    }

    protected function setPanelSetting(): void
    {
        $this->sendCommand([
            JD79661Command::PANEL_SETTING->value,
            $this->panel_setting_1,
            $this->panel_setting_2
        ]);
    }

    protected function setPowerSetting(): void
    {
        $this->sendCommand([
            JD79661Command::POWER_SETTING->value,
            $this->power_setting_1,
            $this->power_setting_2
        ]);
    }

    protected function setPowerOffSequence(): void
    {
        $this->sendCommand([
            JD79661Command::POWER_OFF_SEQUENCE->value,
            ...$this->power_offset_sequence
        ]);
    }

    protected function setBoosterSoftStart(): void
    {
        $this->sendCommand([
            JD79661Command::BOOSTER_SOFT_START->value,
            ...$this->booster_soft_start
        ]);
    }

    protected function setVcomDataInterval(): void
    {
        $this->sendCommand([
            JD79661Command::VCOM_DATA_INTERVAL->value,
            $this->vcom_data_interval
        ]);
    }

    protected function setTcon(): void
    {
        $this->sendCommand([
            JD79661Command::TCON->value,
            ...$this->tcon_setting
        ]);
    }

    protected function setResolution(): void
    {
        // Set full buffer width (128) - this MUST match data width we send
        $this->sendCommand([
            JD79661Command::RESOLUTION_SETTING->value,
            ($this->display_width >> 8) & 0xFF,   // Width high byte (128)
            $this->display_width & 0xFF,          // Width low byte
            ($this->display_height >> 8) & 0xFF,  // Height high byte (250)
            $this->display_height & 0xFF          // Height low byte
        ]);
    }

    protected function setExtendedConfig(): void
    {
        // Config register 0xE7
        $this->sendCommand([
            JD79661Command::CONFIG_E7->value,
            $this->config_e7
        ]);

        // Power saving register
        $this->sendCommand([
            JD79661Command::POWER_SAVING->value,
            $this->power_saving
        ]);

        // Config register 0xB4
        $this->sendCommand([
            JD79661Command::CONFIG_B4->value,
            $this->config_b4
        ]);

        // Config register 0xB5
        $this->sendCommand([
            JD79661Command::CONFIG_B5->value,
            $this->config_b5
        ]);

        // Config register 0xE9
        $this->sendCommand([
            JD79661Command::CONFIG_E9->value,
            $this->config_e9
        ]);
    }

    protected function setPllControl(): void
    {
        $this->sendCommand([
            JD79661Command::PLL_CONTROL->value,
            $this->pll_control
        ]);
    }

    protected function powerOn(): void
    {
        $this->sendCommand([JD79661Command::POWER_ON->value]);
        $this->busyWait(200);
    }

    protected function powerOff(): void
    {
        $this->sendCommand([
            JD79661Command::POWER_OFF->value,
            0x00
        ]);
        $this->busyWait(100);
    }

    protected function deepSleep(): void
    {
        $this->sendCommand([
            JD79661Command::DEEP_SLEEP->value,
            0xA5  // Magic value to enter deep sleep
        ]);
        $this->wait(100);
    }

    protected function clearScreenBuffer(): void
    {
        // Clear screen RAM to white (0x55 = 01010101 = 4 white pixels)
        $this->startWrite();

        $buffer_size = ($this->display_width * $this->display_height) / 4;
        $clear_data = array_fill(0, $buffer_size, 0x55); // All white pixels

        foreach(array_chunk($clear_data, $this->max_packet_size) as $chunk)
        {
            $this->sendData($chunk);
        }
    }

    protected function startWrite(): void
    {
        $this->sendCommand([JD79661Command::DATA_START_TRANSMISSION->value]);
    }

    protected function endWrite(bool $partial = false): void
    {
        $vcom = $partial ? 0x97 : 0x37;
        $this->sendCommand([JD79661Command::VCOM_DATA_INTERVAL->value, $vcom]);
        $this->sendCommand([JD79661Command::DISPLAY_REFRESH->value, 0x00]);
    }

    public function display(): static
    {
        // For full screen, skip window command - resolution was set during boot
        // $this->setWindow(0, 0, $this->display_width, $this->display_height, false);
        $this->startWrite();

        $payload = $this->wire->toRows();

        foreach(array_chunk($payload, $this->max_packet_size) as $chunk)
        {
            $this->sendData($chunk);
        }

        $this->endWrite();
        $this->wait(1);
        $this->busyWait(40000);  // Full refresh can take up to 25 seconds

        return $this;
    }

    protected function setWindow(
        int $x,
        int $y,
        int $width,
        int $height,
        bool $partial = true
    ): void {
        // Apply offset if needed
        $x = $x + $this->x_offset;

        $x_end = $x + $width - 1;
        $y_end = $y + $height - 1;

        $this->sendCommand([
            JD79661Command::PARTIAL_WINDOW->value,
            ($x >> 8) & 0xFF,      // X start high byte
            $x & 0xFF,             // X start low byte
            ($x_end >> 8) & 0xFF,  // X end high byte
            $x_end & 0xFF,         // X end low byte
            ($y >> 8) & 0xFF,      // Y start high byte
            $y & 0xFF,             // Y start low byte
            ($y_end >> 8) & 0xFF,  // Y end high byte
            $y_end & 0xFF,         // Y end low byte
            $partial ? 0x01 : 0x00 // Mode: 0x01=partial, 0x00=full
        ]);
    }
}
