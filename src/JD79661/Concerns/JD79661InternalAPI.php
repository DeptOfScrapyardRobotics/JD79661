<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Concerns;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Enums\JD79661OpCode;

trait JD79661InternalAPI
{
    protected function command(JD79661OpCode $register_hex, array $command_data = []): void
    {
        $this->carrier->command($register_hex, $command_data);
    }

    protected function data(array $data): void
    {
        $this->carrier->data($data);
    }

    protected function waitUntilIdle(): void
    {
        $this->carrier->waitUntilIdle();
    }

    protected function setPower(bool $on): void
    {
        $on ? $this->powerOn() : $this->powerOff();
    }

    /**
     * Clock out one of the undocumented vendor init registers with its fixed
     * manufacturer payload. The silicon refuses to come up without them.
     */
    protected function vendor(JD79661OpCode $register): void
    {
        $this->command($register, $register->vendorPayload());
    }
}
