<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\AllowanceIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\AllowanceItem;

$client = require __DIR__ . '/_bootstrap.php';

try {
    $response = $client->issueAllowance(new AllowanceIssueRequest(
        invoiceNo: 'AA00000076',
        merchantOrderNo: 'ORD_CHANGE_ME',
        totalAmount: 100,
        taxAmount: 5,
        items: [
            new AllowanceItem(name: '商品一', count: 1, unit: '個', price: 95, amount: 95, taxAmount: 5),
        ],
    ));

    echo "AllowanceNo：{$response->allowanceNo()}\n";
    echo "AllowanceAmt：{$response->totalAmount()}\n";
} catch (EzpayException $e) {
    echo $e->getMessage() . "\n";
}
