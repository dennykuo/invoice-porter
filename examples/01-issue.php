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
    // 0.5.0 起 printFlag 為必填：是否寄送紙本是業務語意決策，不再有預設值。
    printFlag: PrintFlag::No,
    buyerEmail: 'demo@example.com',
    // B2C + PrintFlag=N 時 SDK 會要求 carrier 或 loveCode 擇一（0.5.0 起涵蓋全 TaxType）；此處示範捐贈碼。
    loveCode: '13994',
);

try {
    $response = $client->issue($request);
    echo "發票號碼：{$response->invoiceNumber()}\n";
    echo "InvoiceTransNo：{$response->invoiceTransNo()}\n";
    echo "RandomNum：{$response->randomNum()}\n";
    // 0.4.0 起 createTimeAt() 直接回 ?DateTimeImmutable，省去自行 parse 'Y-m-d H:i:s' 字串。
    echo '開立時間：' . ($response->createTimeAt()?->format('Y-m-d H:i:s') ?? '-') . "\n";
} catch (EzpayApiException $e) {
    // 0.4.1 起 EzpayApiException 提供錯誤碼語意分群 helper，呼叫端不必再對個別字串做 match。
    if ($e->isDuplicateOrderNo()) {
        // NOR10001 / LIB10003 → 產生新訂單編號重試
        echo "訂單編號重複 [{$e->errorCode}]：建議產生新編號重試\n";
    } elseif ($e->isAuthError()) {
        // INV900xx / KEY100xx → 憑證 / 解密問題，告警 ops 檢查 hashKey / hashIv
        echo "憑證錯誤 [{$e->errorCode}]：請 ops 檢查 hashKey / hashIv 設定\n";
    } elseif ($e->isFieldFormatError()) {
        // INV100xx / INV700xx → 欄位格式錯，引導使用者修正輸入
        echo "欄位格式錯誤 [{$e->errorCode}]：{$e->getMessage()}\n";
    } else {
        echo "藍新業務錯誤 [{$e->errorCode}]：{$e->getMessage()}\n";
    }
} catch (EzpayException $e) {
    echo 'SDK 錯誤：' . $e->getMessage() . "\n";
}
