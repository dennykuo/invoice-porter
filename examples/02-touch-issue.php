<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\InvoiceTouchIssueRequest;

$client = require __DIR__ . '/_bootstrap.php';

try {
    $response = $client->touchIssue(new InvoiceTouchIssueRequest(
        merchantOrderNo: 'ORD_CHANGE_ME',
        totalAmount: 500,
    ));

    echo "InvoiceNumber：{$response->invoiceNumber()}\n";
} catch (EzpayException $e) {
    echo $e->getMessage() . "\n";
}
