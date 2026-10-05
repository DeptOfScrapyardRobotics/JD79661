<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Tests\Support;

use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;

final class FakeDigitalIOConnectionFactory extends DigitalIOConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    protected function getHandle(): string
    {
        return "gpio:{$this->device}";
    }
}
