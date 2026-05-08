<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay;

enum Environment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public function baseUrl(): string
    {
        return match ($this) {
            self::Sandbox => 'https://cinv.ezpay.com.tw/Api/',
            self::Production => 'https://inv.ezpay.com.tw/Api/',
        };
    }
}
