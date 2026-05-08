<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum PrintFlag: string
{
    case Yes = 'Y';
    case No = 'N';
}
