<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\InvoiceInvalidRequest;

$client = require __DIR__ . '/_bootstrap.php';

try {
    $response = $client->invalid(new InvoiceInvalidRequest(
        invoiceNumber: 'AA00000076',
        invalidReason: '訂單取消',
    ));

    echo "已作廢：{$response->invoiceNumber()} @ {$response->createTime()}\n";
} catch (EzpayException $e) {
    echo $e->getMessage() . "\n";
}
