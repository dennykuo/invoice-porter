<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Requests\Track\TrackManageRequest;

$client = require __DIR__ . '/_bootstrap-track.php';

// 把字軌設為「停止使用」（例如某段字軌已用完或退回國稅局）
$request = new TrackManageRequest(
    managementNo: 'YOUR_MANAGEMENT_NO',  // 由 trackCreate / trackSearch 取得
    flag: TrackFlag::Disabled,           // 0=暫停、1=正常、2=停止
);

try {
    $response = $client->trackManage($request);
    echo "字軌：{$response->managementNo()}\n";
    echo "目前狀態：{$response->flag()?->value}\n";  // 0/1/2
} catch (EzpayApiException $e) {
    echo "藍新業務錯誤 [{$e->errorCode}]：{$e->getMessage()}\n";
} catch (EzpayException $e) {
    echo 'SDK 錯誤：' . $e->getMessage() . "\n";
}
