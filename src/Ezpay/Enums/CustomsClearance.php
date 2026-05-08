<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum CustomsClearance: string
{
    case NotThroughCustoms = '1';
    case ThroughCustoms = '2';
}
