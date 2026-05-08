<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests;

use InvoicePorter\Ezpay\Enums\AllowanceTouchStatus;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\AllowanceTouchIssueRequest;
use PHPUnit\Framework\TestCase;

final class AllowanceTouchIssueRequestTest extends TestCase
{
    public function testConfirmPayload(): void
    {
        $request = new AllowanceTouchIssueRequest(
            allowanceNo: 'A001',
            status: AllowanceTouchStatus::Confirm,
        );

        $this->assertSame('allowance_touch_issue', $request->uri());
        $this->assertSame('1.0', $request->version());
        $payload = $request->toEncryptablePayload();
        $this->assertSame('A001', $payload['AllowanceNo']);
        $this->assertSame('C', $payload['Status']);
    }

    public function testRejectsEmptyAllowanceNo(): void
    {
        $this->expectException(EzpayValidationException::class);
        new AllowanceTouchIssueRequest(allowanceNo: '', status: AllowanceTouchStatus::Confirm);
    }
}
