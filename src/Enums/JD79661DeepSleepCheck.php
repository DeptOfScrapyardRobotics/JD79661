<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Enums;

/** Deep Sleep (0x07) takes this check code; any other byte is ignored. */
enum JD79661DeepSleepCheck: int
{
    case CODE = 0xA5;
}
