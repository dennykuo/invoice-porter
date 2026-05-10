<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\AllowanceInvalidRequest;

$client = require __DIR__ . '/_bootstrap.php';

try {
    $response = $client->invalidAllowance(new AllowanceInvalidRequest(
        allowanceNo: 'A001',
        invalidReason: '客戶取消',
    ));

    echo "已作廢折讓：{$response->allowanceNo()}\n";
} catch (EzpayException $e) {
    echo $e->getMessage() . "\n";
}
