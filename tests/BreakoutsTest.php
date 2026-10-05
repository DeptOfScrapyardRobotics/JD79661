<?php

use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661BoosterSoftStart;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PanelSetting;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661PowerOffSequence;
use DeptOfScrapyardRobotics\Displays\JD79661\Breakouts\JD79661TCON;
use DeptOfScrapyardRobotics\Displays\JD79661\Enums\JD79661VendorRegister;
use DeptOfScrapyardRobotics\Displays\JD79661\JD79661Exception;

it('carries the reference bring-up values', function (): void {
    expect((new JD79661PanelSetting)->toBytes())->toBe([0x8F, 0x29])
        ->and((new JD79661PowerOffSequence)->toBytes())->toBe([0x10, 0x54, 0x44])
        ->and((new JD79661BoosterSoftStart)->toBytes())->toBe([0x05, 0x00, 0x3F, 0x0A, 0x25, 0x12, 0x1A])
        ->and(JD79661TCON::fromBytes([0x03, 0x04])->toBytes())->toBe([0x03, 0x04])
        ->and(JD79661VendorRegister::MAGIC_INIT->payload())->toBe(0x78);
});

it('refuses register values out of range instead of masking them', function (callable $build, string $message): void {
    expect($build)->toThrow(JD79661Exception::class, $message);
})->with([
    'panel' => [fn () => new JD79661PanelSetting(byte0: 0x100), 'byte0 takes 0 to 255; got 256'],
    'tcon' => [fn () => new JD79661TCON(g2s: -1), 'g2s takes 0 to 255; got -1'],
    'booster count' => [fn () => new JD79661BoosterSoftStart(1, 2, 3), 'booster byte count takes 7 to 7; got 3'],
    'booster byte' => [fn () => new JD79661BoosterSoftStart(1, 2, 3, 4, 5, 6, 300), 'booster byte 6 takes 0 to 255'],
]);
