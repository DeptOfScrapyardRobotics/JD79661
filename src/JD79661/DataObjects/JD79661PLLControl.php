<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions\JD79661Exception;

/**
 * PLL Control — PLL (0x30), single parameter byte.
 *
 * Sets the frame rate / PLL clock divider used to generate the panel scan
 * timing. The default 0x08 matches the JD79661 reference bring-up.
 */
readonly class JD79661PLLControl
{
    public function __construct(
        public int $frame_rate = 0x08,
    ) {
        if (($this->frame_rate < 0) || ($this->frame_rate > 0xFF)) {
            throw JD79661Exception::invalidRegisterValue('frame_rate', $this->frame_rate, 0, 0xFF);
        }
    }

    /**
     * @return list<int>
     */
    public function toBytes(): array
    {
        return [$this->frame_rate & 0xFF];
    }
}
