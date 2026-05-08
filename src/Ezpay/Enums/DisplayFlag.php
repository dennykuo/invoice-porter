<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum DisplayFlag: string
{
    case Redirect = '1';
    case ResultUrl = '2';
}
