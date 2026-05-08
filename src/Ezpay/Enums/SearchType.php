<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum SearchType: string
{
    case ByInvoiceNumber = '0';
    case ByMerchantOrderNo = '1';
}
