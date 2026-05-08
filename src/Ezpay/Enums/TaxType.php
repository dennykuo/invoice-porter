<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum TaxType: string
{
    case Taxable = '1';
    case ZeroRate = '2';
    case Exempt = '3';
    case Mixed = '9';
}
