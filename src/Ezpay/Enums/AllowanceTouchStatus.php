<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum AllowanceTouchStatus: string
{
    case Confirm = 'C';
    case Deny = 'D';
}
