<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\AllowanceIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\AllowanceItem;
use PHPUnit\Framework\TestCase;

final class AllowanceIssueRequestTest extends TestCase
{
    public function testValidPayload(): void
    {
        $request = new AllowanceIssueRequest(
            invoiceNo: 'AA00000076',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 100,
            taxAmount: 5,
            items: [new AllowanceItem(name: '商品一', count: 1, unit: '個', price: 95, amount: 95, taxAmount: 5)],
        );

        $this->assertSame('allowance_issue', $request->uri());
        $this->assertSame('1.3', $request->version());
        $this->assertNull($request->checkCodeFields(), '預設不驗 CheckCode');

        $payload = $request->toEncryptablePayload();
        $this->assertSame('AA00000076', $payload['InvoiceNo']);
        $this->assertSame('100', $payload['TotalAmt']);
        $this->assertSame('5', $payload['TaxAmt']);
        $this->assertSame('1', $payload['Status']);
    }

    public function testExpectCheckCodeOptIn(): void
    {
        $request = new AllowanceIssueRequest(
            invoiceNo: 'AA00000076',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 100,
            taxAmount: 5,
            items: [new AllowanceItem(name: '商品一', count: 1, unit: '個', price: 95, amount: 95)],
            expectCheckCode: true,
        );

        $this->assertNotNull($request->checkCodeFields());
    }

    public function testRejectsEmptyItems(): void
    {
        $this->expectException(EzpayValidationException::class);
        new AllowanceIssueRequest(
            invoiceNo: 'AA00000076',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 100,
            taxAmount: 5,
            items: [],
        );
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(EzpayValidationException::class);
        new AllowanceIssueRequest(
            invoiceNo: 'AA00000076',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 100,
            taxAmount: 5,
            items: [new AllowanceItem(name: '商品一', count: 1, unit: '個', price: 95, amount: 95)],
            buyerEmail: 'not-email',
        );
    }

    public function testRejectsHyphenInMerchantOrderNo(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('英文、數字、底線');

        new AllowanceIssueRequest(
            invoiceNo: 'AA00000076',
            merchantOrderNo: 'ORD-001',
            totalAmount: 100,
            taxAmount: 5,
            items: [new AllowanceItem(name: '商品一', count: 1, unit: '個', price: 95, amount: 95)],
        );
    }

    public function testRejectsBuyerEmailOver80(): void
    {
        // 0.5.1 修補：過去 AllowanceIssueRequest 只查格式不查長度，與 InvoiceIssueRequest 不一致
        $tooLong = str_repeat('a', 60) . '@' . str_repeat('b', 17) . '.com';
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('80');

        new AllowanceIssueRequest(
            invoiceNo: 'AA00000076',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 100,
            taxAmount: 5,
            items: [new AllowanceItem(name: '商品一', count: 1, unit: '個', price: 95, amount: 95)],
            buyerEmail: $tooLong,
        );
    }
}
