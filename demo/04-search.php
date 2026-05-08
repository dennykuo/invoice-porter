<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\InvoiceSearchRequest;

$client = require __DIR__ . '/_bootstrap.php';

try {
    $response = $client->search(new InvoiceSearchRequest(
        searchType: SearchType::ByInvoiceNumber,
        merchantOrderNo: 'ORD-CHANGE-ME',
        invoiceNumber: 'AA00000076',
        randomNum: '0991',
    ));

    echo "InvoiceNumber：{$response->invoiceNumber()}\n";
    echo "Lifecycle：{$response->lifecycleStatus()?->value}\n";
} catch (EzpayException $e) {
    echo $e->getMessage() . "\n";
}
