<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Adapters\JD79661DataCarrier;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Concerns\JD79661API;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661BoosterSoftStart;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PanelSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PLLControl;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PowerOffSequence;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PowerSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661TCON;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661VcomDataInterval;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Enums\JD79661OpCode;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions\JD79661Exception;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Factory\JD79661Factory;
use Exception;
use RealityInterface\Displays\Applied\ePaper\Enums\EInkColor;
use RealityInterface\Displays\Attributes\OutputsFourColors;
use RealityInterface\Displays\Attributes\OutputsOnlyBlackAndWhite;
use RealityInterface\Displays\Attributes\OutputsThreeColors;
use RealityInterface\Displays\Contracts\Applied\ePaper\QuadColorEInkDisplay;
use RealityInterface\Displays\EmbeddedDisplay;
use ScrapyardIO\NutsAndBolts\DataObjects\ChannelPalette;
use ScrapyardIO\NutsAndBolts\DataObjects\ChannelSpec;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\ScanDirection;
use Waveforms\Carriers\GPIO\GPIO;
use Waveforms\Carriers\SPI\SPI;

#[OutputsOnlyBlackAndWhite]
#[OutputsThreeColors]
#[OutputsFourColors]
class JD79661 extends EmbeddedDisplay implements QuadColorEInkDisplay
{
    use JD79661API;

    protected bool $booted = false;

    /**
     * @throws Exception
     */
    public function __construct(
        protected readonly JD79661DataCarrier $carrier,
        int $width,
        int $height,
        JD79661PanelSetting $panel_setting,
        JD79661PowerSetting $power_setting,
        JD79661PowerOffSequence $power_off_sequence,
        JD79661BoosterSoftStart $booster_soft_start,
        JD79661VcomDataInterval $vcom_data_interval,
        JD79661TCON $tcon,
        JD79661PLLControl $pll_control,
    ) {
        parent::__construct($width, $height);
        $this->boot(
            $panel_setting,
            $power_setting,
            $power_off_sequence,
            $booster_soft_start,
            $vcom_data_interval,
            $tcon,
            $pll_control,
        );
    }

    /**
     * @throws Exception
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
            'power' => $this->setPower((bool) $value),
            default => throw JD79661Exception::invalidProperty($name)
        };
    }

    /**
     * Run the JD79661 4-colour (black/white/red/yellow) power-on sequence.
     *
     * Reproduced from the manufacturer reference bring-up (cross-checked
     * against Adafruit's tested driver): hardware reset, the mandatory 0x4D
     * magic-init unlock, then the documented power/panel/booster/timing
     * registers interleaved with the vendor-tuned magic registers
     * (0xE7/0xE3/0xB4/0xB5/0xE9), and finally power-on.
     *
     * This panel packs all four colours into a single RAM at 2 bits per pixel
     * with the OTP-resident LUT, so we deliberately push no custom LUT. The
     * RESOLUTION (0x61) source axis is rounded up to a byte boundary, which is
     * what the chip expects (122 -> 128). Streaming pixel RAM (0x10) and firing
     * a refresh (0x12) is left to the (forthcoming) data path.
     *
     * @throws Exception
     */
    protected function boot(
        JD79661PanelSetting $panel_setting,
        JD79661PowerSetting $power_setting,
        JD79661PowerOffSequence $power_off_sequence,
        JD79661BoosterSoftStart $booster_soft_start,
        JD79661VcomDataInterval $vcom_data_interval,
        JD79661TCON $tcon,
        JD79661PLLControl $pll_control,
    ): void {
        if (! $this->booted) {
            $source_bits = intdiv($this->width + 7, 8) * 8;
            $gate_bits = $this->height;

            $this->carrier->reset();
            $this->waitUntilIdle();

            $this->magicInit();

            $this->setPanelSetting($panel_setting);
            $this->setPowerSetting($power_setting);
            $this->setPowerOffSequence($power_off_sequence);
            $this->setBoosterSoftStart($booster_soft_start);
            $this->setVcomDataInterval($vcom_data_interval);
            $this->setTCON($tcon);
            $this->setResolution($source_bits, $gate_bits);

            $this->vendor(JD79661OpCode::CONFIG_E7);
            $this->setPowerSaving();
            $this->vendor(JD79661OpCode::CONFIG_B4);
            $this->vendor(JD79661OpCode::CONFIG_B5);
            $this->vendor(JD79661OpCode::CONFIG_E9);

            $this->setPLLControl($pll_control);

            $this->powerOn();

            $this->booted = true;
        }
    }

    /**
     * The JD79661 packs all four colors into one RAM at 2 bits per pixel. We
     * still draw into three 1bpp channel planes (black, red, yellow; white is
     * the absence of all three) and merge them into the 2bpp frame at transmit
     * time. The planes are straight (1 = color present) so the merge reads
     * cleanly. If a color comes out wrong on your panel, adjust the 2bpp codes
     * in {@see colorCode()}.
     */
    public function generateFormatSpec(): FormatSpec
    {
        return new FormatSpec(
            PixelFormat::MONO_HORIZONTAL,
            BitDepth::B1,
            ScanDirection::TOP_TO_BOTTOM,
            bit_order: BitOrder::MSB_FIRST,
            palette: new ChannelPalette(
                new ChannelSpec(EInkColor::BLACK->value),
                new ChannelSpec(EInkColor::RED->value),
                new ChannelSpec(EInkColor::YELLOW->value),
            ),
        );
    }

    public function display(DumpedBuffer $buffer): void
    {
        $frame = $this->packQuadColor(
            $buffer->raw_data[EInkColor::BLACK->value] ?? [],
            $buffer->raw_data[EInkColor::RED->value] ?? [],
            $buffer->raw_data[EInkColor::YELLOW->value] ?? [],
        );

        $this->writeFrame($frame);
        $this->refresh();
    }

    /**
     * @throws Exception
     */
    public static function connection(string $driver): JD79661Factory
    {
        return new JD79661Factory(
            SPI::connection($driver),
            GPIO::connection($driver)
        );
    }

    /**
     * Merge the three 1bpp color planes into the panel's single 2bpp RAM frame.
     *
     * Each plane is 1bpp horizontal (8 px/byte, MSB-first, 1 = color present).
     * We resolve every pixel to a 2-bit code and pack four pixels per byte, the
     * leftmost pixel in the high bits, across the byte-padded source width the
     * chip was told to expect (122 -> 128).
     *
     * @param  array<int, int>  $black
     * @param  array<int, int>  $red
     * @param  array<int, int>  $yellow
     * @return array<int, int>
     */
    private function packQuadColor(array $black, array $red, array $yellow): array
    {
        $stride = intdiv($this->width() + 7, 8);
        $padded_width = $stride * 8;
        $height = $this->height();

        $frame = [];
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $padded_width; $x += 4) {
                $byte = 0;
                for ($pixel = 0; $pixel < 4; $pixel++) {
                    $code = $this->colorCode($black, $red, $yellow, $x + $pixel, $y, $stride);
                    $byte |= $code << (6 - ($pixel * 2));
                }
                $frame[] = $byte;
            }
        }

        return $frame;
    }

    /**
     * The panel's 2-bit color code for a pixel: black 0b00, white 0b01,
     * yellow 0b10, red 0b11. White is the fallback when no plane claims it.
     *
     * @param  array<int, int>  $black
     * @param  array<int, int>  $red
     * @param  array<int, int>  $yellow
     */
    private function colorCode(array $black, array $red, array $yellow, int $x, int $y, int $stride): int
    {
        if ($this->planeBit($black, $x, $y, $stride) === 1) {
            return 0b00;
        }

        if ($this->planeBit($red, $x, $y, $stride) === 1) {
            return 0b11;
        }

        if ($this->planeBit($yellow, $x, $y, $stride) === 1) {
            return 0b10;
        }

        return 0b01;
    }

    /**
     * Read one pixel out of a 1bpp horizontal plane (MSB = leftmost). Returns 0
     * for an empty/omitted plane or an out-of-range byte.
     *
     * @param  array<int, int>  $plane
     */
    private function planeBit(array $plane, int $x, int $y, int $stride): int
    {
        $byte = $plane[($y * $stride) + ($x >> 3)] ?? 0;

        return ($byte >> (7 - ($x & 7))) & 1;
    }
}
