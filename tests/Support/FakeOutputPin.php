<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support;

use GeneralPurposeIO\Digital\DigitalOutputTransport;

/** Records every level written. */
final class FakeOutputPin extends DigitalOutputTransport
{
    public bool $state = false;

    /** @var list<bool> */
    public array $levels = [];

    public bool $released = false;

    public function handle(): string
    {
        return 'fake';
    }

    public function read(): bool
    {
        return $this->state;
    }

    public function write(bool $state): bool
    {
        $this->ensureOpen();
        $this->levels[] = $state;

        return $this->state = $state;
    }

    protected function release(): void
    {
        $this->released = true;
    }
}
