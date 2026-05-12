<?php

declare(strict_types=1);

use InvoicePorter\Ezpay\Enums\DisplayFlag;
use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Requests\InvoiceSearchRequest;

$client = require __DIR__ . '/_bootstrap.php';

// ──────────────────────────────────────────────────────────
// 寫法 A：手動組 InvoiceSearchRequest + DisplayFlag::Redirect
// ──────────────────────────────────────────────────────────
$request = new InvoiceSearchRequest(
    searchType: SearchType::ByInvoiceNumber,
    merchantOrderNo: 'ORD_CHANGE_ME',
    invoiceNumber: 'AA00000076',
    randomNum: '0991',
    displayFlag: DisplayFlag::Redirect,
);

// 直接 echo HTML，瀏覽器會自動 submit 到藍新查詢頁
echo $client->searchRedirectHtml($request);

// ──────────────────────────────────────────────────────────
// 寫法 B（0.4.0+ sugar）：給四欄即可，不必先組 Request
// ──────────────────────────────────────────────────────────
// echo $client->publicQueryRedirectHtml(
//     invoiceNumber: 'AA00000076',
//     randomNum: '0991',
//     merchantOrderNo: 'ORD_CHANGE_ME',
//     totalAmount: 500,
// );

// ──────────────────────────────────────────────────────────
// 寫法 C（0.4.0+ sugar）：剛 issue 完，直接從 Response 產生 Request 再丟回 searchRedirectHtml()
// 適合「發票開立後立即跳查詢頁」的典型情境
// ──────────────────────────────────────────────────────────
// $issueResponse = $client->issue($issueRequest);
// echo $client->searchRedirectHtml($issueResponse->toSearchRequest());
