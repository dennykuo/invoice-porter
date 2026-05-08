<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum RespondType: string
{
    case Json = 'JSON';
    case String = 'String';
}
