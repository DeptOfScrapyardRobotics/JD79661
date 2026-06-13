<?php

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Adapters\JD79661DataCarrier;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661BoosterSoftStart;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PanelSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PLLControl;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PowerOffSequence;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661PowerSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661TCON;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects\JD79661VcomDataInterval;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Enums\JD79661OpCode;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\JD79661;
use RealityInterface\Displays\Applied\ePaper\Enums\EInkColor;
use RealityInterface\Displays\Applied\ePaper\QuadColorePaperDisplay;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;

/*
 | The BWRY panel is driven through a recording carrier (no SPI/GPIO), so the
 | boot sequence and the 2bpp merge/data path run unchanged on a Mac. We
 | snapshot only the opcodes the data path emits by clearing the recorder after
 | construction.
 */

class RecordingJD79661Carrier extends JD79661DataCarrier
{
    /**
     * @var array<int, array{0: JD79661OpCode, 1: array<int, int>}>
     */
    public array $commands = [];

    public function __construct() {}

    public function data(array $data): void {}

    public function command(JD79661OpCode $register_hex, array $command_data = []): void
    {
        $this->commands[] = [$register_hex, $command_data];
    }

    public function reset(): void {}

    public function waitUntilIdle(): void {}
}

function makeJD79661Panel(RecordingJD79661Carrier $carrier, int $width = 8, int $height = 1): JD79661
{
    return new JD79661(
        $carrier,
        $width,
        $height,
        new JD79661PanelSetting,
        new JD79661PowerSetting,
        new JD79661PowerOffSequence,
        new JD79661BoosterSoftStart,
        new JD79661VcomDataInterval,
        new JD79661TCON,
        new JD79661PLLControl,
    );
}

/**
 * @return array<int, JD79661OpCode>
 */
function jd79661Opcodes(RecordingJD79661Carrier $carrier): array
{
    return array_map(fn (array $entry): JD79661OpCode => $entry[0], $carrier->commands);
}

/**
 * @return array<int, int>
 */
function jd79661PayloadFor(RecordingJD79661Carrier $carrier, JD79661OpCode $opcode): array
{
    foreach ($carrier->commands as [$op, $data]) {
        if ($op === $opcode) {
            return $data;
        }
    }

    return [];
}

it('exposes a black/red/yellow channel-sorted format spec', function () {
    $spec = makeJD79661Panel(new RecordingJD79661Carrier)->getFormatSpec();

    expect($spec->pixel_format)->toBe(PixelFormat::MONO_HORIZONTAL)
        ->and($spec->bit_depth)->toBe(BitDepth::B1)
        ->and($spec->palette?->count())->toBe(3)
        ->and($spec->palette?->channels[0]->color)->toBe(EInkColor::BLACK->value)
        ->and($spec->palette?->channels[0]->inverted)->toBeFalse()
        ->and($spec->palette?->channels[1]->color)->toBe(EInkColor::RED->value)
        ->and($spec->palette?->channels[1]->inverted)->toBeFalse()
        ->and($spec->palette?->channels[2]->color)->toBe(EInkColor::YELLOW->value)
        ->and($spec->palette?->channels[2]->inverted)->toBeFalse();
});

it('merges the three color planes into one 2bpp frame then refreshes', function () {
    $carrier = new RecordingJD79661Carrier;
    $panel = makeJD79661Panel($carrier);
    $carrier->commands = [];

    // px0 black, px1 red, px2 yellow, px3-7 white.
    $panel->display(new DumpedBuffer(
        RenderType::FULL,
        $panel->getFormatSpec(),
        [
            EInkColor::BLACK->value => [0x80],
            EInkColor::RED->value => [0x40],
            EInkColor::YELLOW->value => [0x20],
        ],
        width: 8,
        height: 1,
    ));

    // Codes: black 0b00, white 0b01, yellow 0b10, red 0b11; high pixel first.
    // byte0 = 00 11 10 01 = 0x39, byte1 = 01 01 01 01 = 0x55.
    expect(jd79661Opcodes($carrier))->toBe([
        JD79661OpCode::DATA_START_TRANSMISSION,
        JD79661OpCode::DISPLAY_REFRESH,
    ])
        ->and(jd79661PayloadFor($carrier, JD79661OpCode::DATA_START_TRANSMISSION))->toBe([0x39, 0x55])
        ->and(jd79661PayloadFor($carrier, JD79661OpCode::DISPLAY_REFRESH))->toBe([0x00]);
});

it('treats omitted color channels as white background', function () {
    $carrier = new RecordingJD79661Carrier;
    $panel = makeJD79661Panel($carrier);
    $carrier->commands = [];

    $panel->display(new DumpedBuffer(
        RenderType::FULL,
        $panel->getFormatSpec(),
        [],
        width: 8,
        height: 1,
    ));

    // Every pixel white (0b01) => 0x55 per byte across the byte-padded row.
    expect(jd79661PayloadFor($carrier, JD79661OpCode::DATA_START_TRANSMISSION))->toBe([0x55, 0x55]);
});

it('drives the panel through the quad-color ePaper wrapper', function () {
    $carrier = new RecordingJD79661Carrier;
    $panel = makeJD79661Panel($carrier);
    $screen = QuadColorePaperDisplay::as($panel);
    $carrier->commands = [];

    $screen->transmit(new DumpedBuffer(
        RenderType::FULL,
        $panel->getFormatSpec(),
        [EInkColor::RED->value => [0x80]],
        width: 8,
        height: 1,
    ));

    expect(jd79661Opcodes($carrier))->toContain(JD79661OpCode::DATA_START_TRANSMISSION)
        ->and(jd79661Opcodes($carrier))->toContain(JD79661OpCode::DISPLAY_REFRESH);
});
