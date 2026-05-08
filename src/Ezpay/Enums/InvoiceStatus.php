<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum InvoiceStatus: string
{
    case Pending = '0';
    case Immediate = '1';
    case Scheduled = '3';
}
