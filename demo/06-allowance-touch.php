<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Enums\AllowanceTouchStatus;
use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\AllowanceTouchIssueRequest;

$client = require __DIR__ . '/_bootstrap.php';

try {
    $response = $client->touchAllowance(new AllowanceTouchIssueRequest(
        allowanceNo: 'A001',
        status: AllowanceTouchStatus::Confirm,
    ));

    echo "Touch 結果：{$response->allowanceNo()}\n";
} catch (EzpayException $e) {
    echo $e->getMessage() . "\n";
}
