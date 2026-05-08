<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum InvoiceLifecycleStatus: string
{
    case Issued = '1';
    case Voided = '2';
}
