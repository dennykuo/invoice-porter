<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

/**
 * 字軌狀態旗標。
 *
 * 對應藍新 EZP_Track_1.0.0 文件「Flag」欄位：
 * 0=暫停使用、1=正常使用、2=停止使用
 */
enum TrackFlag: string
{
    case Paused = '0';
    case Active = '1';
    case Disabled = '2';
}
