<?php

namespace DeptOfScrapyardRobotics\Displays\JD79661\Enums;

/** JD79661 command codes. */
enum JD79661OpCode: int
{
    case PANEL_SETTING = 0x00;
    case POWER_SETTING = 0x01;
    case POWER_OFF = 0x02;
    case POWER_OFF_SEQUENCE = 0x03;
    case POWER_ON = 0x04;
    case BOOSTER_SOFT_START = 0x06;
    case DEEP_SLEEP = 0x07;
    case DATA_START_TRANSMISSION = 0x10;
    case DISPLAY_REFRESH = 0x12;
    case PLL_CONTROL = 0x30;
    case VCOM_DATA_INTERVAL = 0x50;
    case TCON = 0x60;
    case RESOLUTION_SETTING = 0x61;
}
