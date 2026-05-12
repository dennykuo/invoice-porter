<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\InvoiceTouchIssueRequest;
use PHPUnit\Framework\TestCase;

final class InvoiceTouchIssueRequestTest extends TestCase
{
    public function testValidRequestProducesExpectedPayload(): void
    {
        $request = new InvoiceTouchIssueRequest(
            merchantOrderNo: 'ORD20260101',
            totalAmount: 500,
        );

        $this->assertSame('invoice_touch_issue', $request->uri());
        $this->assertSame('1.0', $request->version());

        $payload = $request->toEncryptablePayload();
        $this->assertSame('ORD20260101', $payload['MerchantOrderNo']);
        $this->assertSame('500', $payload['TotalAmt']);
    }

    public function testRejectsEmptyOrderNo(): void
    {
        $this->expectException(EzpayValidationException::class);
        new InvoiceTouchIssueRequest(merchantOrderNo: '', totalAmount: 500);
    }

    public function testRejectsZeroTotalAmount(): void
    {
        $this->expectException(EzpayValidationException::class);
        new InvoiceTouchIssueRequest(merchantOrderNo: 'X', totalAmount: 0);
    }

    public function testRejectsHyphenInMerchantOrderNo(): void
    {
        // 統一走 MerchantOrderNoValidator（過去此 Request 只查空，是漂移之一）
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('英文、數字、底線');

        new InvoiceTouchIssueRequest(merchantOrderNo: 'ORD-001', totalAmount: 500);
    }
}
