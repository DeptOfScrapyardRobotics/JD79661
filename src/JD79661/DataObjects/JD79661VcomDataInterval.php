<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\JD79661\DataObjects;

use DeptOfScrapyardRobotics\Displays\JD79661\JD79661\Exceptions\JD79661Exception;

/**
 * VCOM and Data Interval Setting — CDI (0x50), single parameter byte.
 *
 * Sets the VCOM-to-data interval and the border/data polarity for the refresh
 * waveform. The default 0x37 matches the JD79661 reference bring-up.
 */
readonly class JD79661VcomDataInterval
{
    public function __construct(
        public int $interval = 0x37,
    ) {
        if (($this->interval < 0) || ($this->interval > 0xFF)) {
            throw JD79661Exception::invalidRegisterValue('interval', $this->interval, 0, 0xFF);
        }
    }

    /**
     * @return list<int>
     */
    public function toBytes(): array
    {
        return [$this->interval & 0xFF];
    }
}
