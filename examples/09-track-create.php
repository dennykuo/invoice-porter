<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\InvoiceType;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\Track\TrackCreateRequest;

$client = require __DIR__ . '/_bootstrap-track.php';

$request = new TrackCreateRequest(
    year: '115',                  // 民國 115 年
    term: InvoiceTerm::JanFeb,    // 一二月
    aphabeticLetter: 'AB',        // 國稅局配發的字軌字母（兩碼大寫）
    startNumber: '00000000',      // 起號 8 碼
    endNumber: '00000049',        // 訖號 8 碼
    type: InvoiceType::General,   // 07 一般稅額（預設）
);

try {
    $response = $client->trackCreate($request);
    echo "字軌管理編號：{$response->managementNo()}\n";
    echo "字軌字母：{$response->aphabeticLetter()}\n";
    echo "起訖：{$response->startNumber()} ~ {$response->endNumber()}\n";
    echo "建立時間：{$response->createDatetime()}\n";
} catch (EzpayApiException $e) {
    echo "藍新業務錯誤 [{$e->errorCode}]：{$e->getMessage()}\n";
} catch (EzpayException $e) {
    echo 'SDK 錯誤：' . $e->getMessage() . "\n";
}
