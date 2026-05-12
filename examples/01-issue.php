<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Enums\Category;
use InvoicePorter\Ezpay\Enums\InvoiceStatus;
use InvoicePorter\Ezpay\Enums\PrintFlag;
use InvoicePorter\Ezpay\Enums\TaxType;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\InvoiceIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\InvoiceItem;

$client = require __DIR__ . '/_bootstrap.php';

$totalAmount = 500;
$amount = (int) round($totalAmount / 1.05);
$tax = $totalAmount - $amount;

$request = new InvoiceIssueRequest(
    status: InvoiceStatus::Immediate,
    merchantOrderNo: 'ORD_' . date('YmdHis'),
    category: Category::B2C,
    taxType: TaxType::Taxable,
    amount: $amount,
    taxAmount: $tax,
    totalAmount: $totalAmount,
    items: [
        new InvoiceItem(name: '商品一', count: 1, unit: '個', price: $totalAmount, amount: $totalAmount),
    ],
    printFlag: PrintFlag::No,
    buyerEmail: 'demo@example.com',
    // B2C + PrintFlag=N 時 SDK 會要求 carrier 或 loveCode 擇一；此處示範捐贈碼。
    loveCode: '13994',
);

try {
    $response = $client->issue($request);
    echo "發票號碼：{$response->invoiceNumber()}\n";
    echo "InvoiceTransNo：{$response->invoiceTransNo()}\n";
    echo "RandomNum：{$response->randomNum()}\n";
} catch (EzpayApiException $e) {
    echo "藍新業務錯誤 [{$e->errorCode}]：{$e->getMessage()}\n";
} catch (EzpayException $e) {
    echo "SDK 錯誤：" . $e->getMessage() . "\n";
}
