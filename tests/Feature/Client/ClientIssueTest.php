<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Enums\Category;
use InvoicePorter\Ezpay\Enums\InvoiceStatus;
use InvoicePorter\Ezpay\Enums\PrintFlag;
use InvoicePorter\Ezpay\Enums\TaxType;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayCheckCodeException;
use InvoicePorter\Ezpay\Requests\InvoiceIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\InvoiceItem;

final class ClientIssueTest extends ClientTestCase
{
    public function testIssueSuccessReturnsTypedResponse(): void
    {
        $body = $this->makeEnvelope('SUCCESS', '開立發票成功', [
            'MerchantID' => self::MERCHANT_ID,
            'InvoiceTransNo' => '20260101000000001',
            'MerchantOrderNo' => 'ORD20260101',
            'TotalAmt' => '500',
            'RandomNum' => '0991',
            'InvoiceNumber' => 'AA00000076',
            'CreateTime' => '2026-01-01 12:00:00',
        ], signCheckCode: true);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->issue($this->buildRequest());

        $this->assertSame('SUCCESS', $response->status());
        $this->assertSame('AA00000076', $response->invoiceNumber());
        $this->assertSame('20260101000000001', $response->invoiceTransNo());

        $sent = $this->decryptLastRequest();
        $this->assertSame(self::MERCHANT_ID, $sent['merchantId']);
        $this->assertSame('1.5', $sent['postData']['Version']);
        $this->assertSame('invoice_issue', basename($this->getLastRequestUri()));
        $this->assertSame('ORD20260101', $sent['postData']['MerchantOrderNo']);
    }

    public function testApiErrorRaisesEzpayApiException(): void
    {
        $body = json_encode([
            'Status' => 'KEY10002',
            'Message' => '解密失敗',
            'Result' => '',
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        try {
            $client->issue($this->buildRequest());
            $this->fail('預期應拋出 EzpayApiException');
        } catch (EzpayApiException $e) {
            $this->assertSame('KEY10002', $e->errorCode);
            $this->assertSame('解密失敗', $e->getMessage());
        }
    }

    public function testCheckCodeMismatchRaisesException(): void
    {
        // 正確結構但 CheckCode 寫死錯誤值
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '',
            'Result' => json_encode([
                'MerchantID' => self::MERCHANT_ID,
                'InvoiceTransNo' => 'IT001',
                'MerchantOrderNo' => 'ORD20260101',
                'TotalAmt' => '500',
                'RandomNum' => '0991',
                'InvoiceNumber' => 'AA00000076',
                'CheckCode' => str_repeat('X', 64),
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $this->expectException(EzpayCheckCodeException::class);
        $client->issue($this->buildRequest());
    }

    private function buildRequest(): InvoiceIssueRequest
    {
        return new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2C,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500)],
            printFlag: PrintFlag::No,
            loveCode: '13994',
        );
    }
}
