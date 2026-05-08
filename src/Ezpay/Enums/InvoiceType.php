<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum InvoiceType: string
{
    case General = '07';
    case Special = '08';
}
