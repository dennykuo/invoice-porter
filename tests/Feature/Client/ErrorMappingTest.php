<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Enums\Category;
use InvoicePorter\Ezpay\Enums\InvoiceStatus;
use InvoicePorter\Ezpay\Enums\TaxType;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Requests\InvoiceIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\InvoiceItem;

final class ErrorMappingTest extends ClientTestCase
{
    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function errorCodes(): array
    {
        return [
            'KEY10002 解密錯誤' => ['KEY10002', '解密錯誤'],
            'INV10003 必要參數不齊' => ['INV10003', '必要參數不齊'],
            'LIB10005 商店不存在' => ['LIB10005', '商店不存在'],
            'NOR10001 訂單編號重複' => ['NOR10001', '訂單編號重複'],
            'IAI10001 發票號碼錯誤' => ['IAI10001', '發票號碼錯誤'],
        ];
    }

    /**
     * @dataProvider errorCodes
     */
    public function testMapsErrorCodeToException(string $code, string $message): void
    {
        $body = json_encode([
            'Status' => $code,
            'Message' => $message,
            'Result' => '',
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $request = new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2C,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500)],
        );

        try {
            $client->issue($request);
            $this->fail('應拋出 EzpayApiException');
        } catch (EzpayApiException $e) {
            $this->assertSame($code, $e->errorCode);
            $this->assertSame($message, $e->getMessage());
        }
    }
}
