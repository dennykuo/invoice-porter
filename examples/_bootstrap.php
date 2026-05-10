<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use InvoicePorter\Ezpay\EzpayConfig;
use InvoicePorter\Ezpay\EzpayInvoiceClient;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

return new EzpayInvoiceClient(EzpayConfig::fromEnv());
