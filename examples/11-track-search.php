<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\Track\TrackSearchRequest;

$client = require __DIR__ . '/_bootstrap-track.php';

// 查 115 年一二月、狀態為「正常」的所有字軌
$request = new TrackSearchRequest(
    year: '115',
    term: InvoiceTerm::JanFeb,
    flag: TrackFlag::Active,
);

try {
    $response = $client->trackSearch($request);

    foreach ($response->items() as $i => $item) {
        $idx = $i + 1;
        $aphabeticLetter = $item['AphabeticLetter'] ?? '';
        $startNumber = $item['StartNumber'] ?? '';
        $endNumber = $item['EndNumber'] ?? '';
        $lastNumber = $item['LastNumber'] ?? '';
        $managementNo = $item['ManagementNo'] ?? '';
        echo "[{$idx}] {$managementNo} | {$aphabeticLetter} {$startNumber}~{$endNumber} | 已用至 {$lastNumber}\n";
    }
} catch (EzpayApiException $e) {
    echo "藍新業務錯誤 [{$e->errorCode}]：{$e->getMessage()}\n";
} catch (EzpayException $e) {
    echo 'SDK 錯誤：' . $e->getMessage() . "\n";
}
