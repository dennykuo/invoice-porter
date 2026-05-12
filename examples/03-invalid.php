<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\InvoiceInvalidRequest;

$client = require __DIR__ . '/_bootstrap.php';

try {
    // 帶齊 randomNum / invoiceTransNo / merchantOrderNo / totalAmount 四欄時，
    // SDK 會啟用 CheckCode 驗證（藍新作廢回應未必含 5 欄，故 SDK 採保守策略，
    // 預設跳過驗證；呼叫端帶齊 4 欄就視為要求驗證）。
    $response = $client->invalid(new InvoiceInvalidRequest(
        invoiceNumber: 'AA00000076',
        invalidReason: '訂單取消',
        // randomNum: '0991',
        // invoiceTransNo: '24050414461511234',
        // merchantOrderNo: 'ORD_CHANGE_ME',
        // totalAmount: 500,
    ));

    echo "已作廢：{$response->invoiceNumber()} @ {$response->createTime()}\n";
} catch (EzpayException $e) {
    echo $e->getMessage() . "\n";
}
