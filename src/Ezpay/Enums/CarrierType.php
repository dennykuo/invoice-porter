<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum CarrierType: string
{
    case Member = '0';
    case Mobile = '1';
    case CitizenDigitalCertificate = '2';
}
