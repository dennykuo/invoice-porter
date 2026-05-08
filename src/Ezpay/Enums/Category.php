<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum Category: string
{
    case B2B = 'B2B';
    case B2C = 'B2C';
}
