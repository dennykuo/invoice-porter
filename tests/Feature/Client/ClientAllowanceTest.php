<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Enums\AllowanceConfirmStatus;
use InvoicePorter\Ezpay\Enums\AllowanceTouchStatus;
use InvoicePorter\Ezpay\Requests\AllowanceInvalidRequest;
use InvoicePorter\Ezpay\Requests\AllowanceIssueRequest;
use InvoicePorter\Ezpay\Requests\AllowanceTouchIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\AllowanceItem;

final class ClientAllowanceTest extends ClientTestCase
{
    public function testIssueAllowanceCallsCorrectUriAndVersion(): void
    {
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '折讓成功',
            'Result' => json_encode([
                'MerchantID' => self::MERCHANT_ID,
                'AllowanceNo' => 'A001',
                'InvoiceNumber' => 'AA00000076',
                'AllowanceAmt' => '100',
                'CreateTime' => '2026-01-03 10:00:00',
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->issueAllowance(new AllowanceIssueRequest(
            invoiceNo: 'AA00000076',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 100,
            taxAmount: 5,
            items: [new AllowanceItem(name: '商品一', count: 1, unit: '個', price: 95, amount: 95, taxAmount: 5)],
            confirmStatus: AllowanceConfirmStatus::Immediate,
        ));

        $this->assertSame('A001', $response->allowanceNo());
        $sent = $this->decryptLastRequest();
        $this->assertSame('allowance_issue', basename($this->getLastRequestUri()));
        $this->assertSame('1.3', $sent['postData']['Version']);
        $this->assertSame('AA00000076', $sent['postData']['InvoiceNo']);
    }

    public function testTouchAllowance(): void
    {
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => 'OK',
            'Result' => json_encode([
                'MerchantID' => self::MERCHANT_ID,
                'AllowanceNo' => 'A001',
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->touchAllowance(new AllowanceTouchIssueRequest(
            allowanceNo: 'A001',
            status: AllowanceTouchStatus::Confirm,
        ));

        $this->assertSame('A001', $response->allowanceNo());
        $sent = $this->decryptLastRequest();
        $this->assertSame('allowance_touch_issue', basename($this->getLastRequestUri()));
        $this->assertSame('C', $sent['postData']['Status']);
    }

    public function testInvalidAllowanceUsesCamelCaseUri(): void
    {
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => 'OK',
            'Result' => json_encode([
                'MerchantID' => self::MERCHANT_ID,
                'AllowanceNo' => 'A001',
                'CreateTime' => '2026-01-04 09:00:00',
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->invalidAllowance(new AllowanceInvalidRequest(
            allowanceNo: 'A001',
            invalidReason: '客戶取消',
        ));

        $this->assertSame('A001', $response->allowanceNo());
        $this->assertSame('allowanceInvalid', basename($this->getLastRequestUri()));
    }
}
