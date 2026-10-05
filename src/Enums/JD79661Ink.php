<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Enums;

/** The panel's 2-bit pixel codes. */
enum JD79661Ink: int
{
    case BLACK = 0b00;
    case WHITE = 0b01;
    case YELLOW = 0b10;
    case RED = 0b11;
}
