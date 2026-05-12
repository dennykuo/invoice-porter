<?php

declare(strict_types=1);

// 也可改用 $client->publicQueryRedirectHtml(...) sugar 一行寫完；
// 開立後直接給 Response 跳查詢頁可用 $client->searchRedirectHtml($response->toSearchRequest()).

use InvoicePorter\Ezpay\Enums\DisplayFlag;
use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Requests\InvoiceSearchRequest;

$client = require __DIR__ . '/_bootstrap.php';

$request = new InvoiceSearchRequest(
    searchType: SearchType::ByInvoiceNumber,
    merchantOrderNo: 'ORD_CHANGE_ME',
    invoiceNumber: 'AA00000076',
    randomNum: '0991',
    displayFlag: DisplayFlag::Redirect,
);

// 直接 echo HTML，瀏覽器會自動 submit 到藍新查詢頁面
echo $client->searchRedirectHtml($request);
