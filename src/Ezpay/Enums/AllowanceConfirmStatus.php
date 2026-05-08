<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum AllowanceConfirmStatus: string
{
    case Pending = '0';
    case Immediate = '1';
}
