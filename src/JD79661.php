<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661;

use DeptOfScrapyardRobotics\Displays\JD79661\Concerns\ConjuresJD79661;
use DeptOfScrapyardRobotics\Displays\JD79661\Concerns\JD79661Bootstrap;
use DeptOfScrapyardRobotics\Displays\JD79661\Enums\JD79661Ink;
use DeptOfScrapyardRobotics\Displays\JD79661\Enums\JD79661OpCode;
use DeptOfScrapyardRobotics\Displays\JD79661\Transports\JD79661DataTransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DisplayPanel;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshesOnCommand;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;
use GeneralPurposeIO\IntegratedCircuits\Bootable;
use Surface\Contracts\Framebuffers\BitDepth;
use Surface\Contracts\Framebuffers\BitOrder;
use Surface\Contracts\Framebuffers\ChannelPalette;
use Surface\Contracts\Framebuffers\ChannelSpec;
use Surface\Contracts\Framebuffers\EInkColor;
use Surface\Contracts\Framebuffers\FormatSpec;
use Surface\Contracts\Framebuffers\PixelFormat;
use Surface\Contracts\Framebuffers\ScanDirection;

/**
 * A four-colour (black, white, yellow, red) ePaper panel on a JD79661. One RAM holds the frame at two bits a pixel,
 * four pixels to a byte with the leftmost in the top bits. The panel takes whole frames only: it is not window
 * addressable, and it has one full refresh.
 */
class JD79661 extends Bootable implements DisplayPanel, RefreshesOnCommand
{
    use ConjuresJD79661;
    use JD79661Bootstrap;

    protected FormatSpec $format_spec;

    public function __construct(
        protected readonly JD79661DataTransport $transport,
        protected JD79661Configuration $props = new JD79661Configuration,
        bool $boot_now = false,
    ) {
        $this->format_spec = $this->generateFormatSpec();

        parent::__construct($boot_now);
    }

    public function width(): int
    {
        return $this->props->get('width');
    }

    public function height(): int
    {
        return $this->props->get('height');
    }

    public function transport(): JD79661DataTransport
    {
        return $this->transport;
    }

    public function config(): JD79661Configuration
    {
        return $this->props;
    }

    public function formatSpec(): FormatSpec
    {
        return $this->format_spec;
    }

    public function setFormatSpec(FormatSpec $format_spec): void
    {
        $this->format_spec = $format_spec;
    }

    /** Packed two-bit palette codes, rows padded to a byte: what a Surface ePaper framebuffer in this spec stores. */
    public function generateFormatSpec(): FormatSpec
    {
        return new FormatSpec(
            PixelFormat::ROW_MAJOR,
            BitDepth::B2,
            ScanDirection::TOP_TO_BOTTOM,
            BitOrder::MSB_FIRST,
            palette: new ChannelPalette(
                new ChannelSpec(EInkColor::BLACK->value, code: JD79661Ink::BLACK->value),
                new ChannelSpec(EInkColor::WHITE->value, code: JD79661Ink::WHITE->value),
                new ChannelSpec(EInkColor::YELLOW->value, code: JD79661Ink::YELLOW->value),
                new ChannelSpec(EInkColor::RED->value, code: JD79661Ink::RED->value),
            ),
        );
    }

    /**
     * Write a whole frame packed per formatSpec(): rows of ceil(width / 4) bytes. The controller's rows span the
     * width rounded up to 8 pixels, so each row is padded with white to that. The panel shows it on refresh().
     *
     * @param  list<int>  $raw_data
     *
     * @throws JD79661Exception for anything but the whole panel, or the wrong byte count
     */
    public function transmit(int $origin_x, int $origin_y, array $raw_data, ?int $frame_width = null, ?int $frame_height = null): void
    {
        $width = $frame_width ?? $this->width();
        $height = $frame_height ?? $this->height();

        if ($origin_x !== 0 || $origin_y !== 0 || $width !== $this->width() || $height !== $this->height()) {
            throw JD79661Exception::wholeFrameOnly($origin_x, $origin_y, $width, $height, $this->width(), $this->height());
        }

        $row = intdiv($width + 3, 4);
        $ram_row = intdiv(intdiv($width + 7, 8) * 8, 4);
        $bytes = array_values($raw_data);

        if (count($bytes) !== $row * $height) {
            throw JD79661Exception::wrongByteCount($row * $height, count($bytes));
        }

        if ($ram_row !== $row) {
            $paper = array_fill(0, $ram_row - $row, $this->paperByte());
            $bytes = array_merge(...array_map(static fn (array $line): array => [...$line, ...$paper], array_chunk($bytes, $row)));
        }

        $this->sendCommand(JD79661OpCode::DATA_START_TRANSMISSION, $bytes);
    }

    /** Display refresh (0x12 0x00), then wait for BUSY to rise. FULL only. */
    public function refresh(RefreshMode $mode = RefreshMode::FULL): void
    {
        if ($mode !== RefreshMode::FULL) {
            throw JD79661Exception::unsupportedRefreshMode($mode);
        }

        $this->sendCommand(JD79661OpCode::DISPLAY_REFRESH, [0x00]);
        $this->waitUntilIdle();
    }

    /** Release DC, RST and BUSY. The bus belongs to its driver and stays open; the panel keeps its image. */
    public function close(): void
    {
        $this->transport->close();
    }

    /** Four white pixels. */
    protected function paperByte(): int
    {
        $white = JD79661Ink::WHITE->value;

        return ($white << 6) | ($white << 4) | ($white << 2) | $white;
    }
}
