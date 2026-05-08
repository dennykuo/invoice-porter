<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\AllowanceInvalidRequest;
use PHPUnit\Framework\TestCase;

final class AllowanceInvalidRequestTest extends TestCase
{
    public function testUriUsesCamelCase(): void
    {
        $request = new AllowanceInvalidRequest(
            allowanceNo: 'A001',
            invalidReason: '客戶取消',
        );

        // 文件附錄為 allowanceInvalid（駝峰），不是底線
        $this->assertSame('allowanceInvalid', $request->uri());
    }

    public function testRejectsLongInvalidReason(): void
    {
        $this->expectException(EzpayValidationException::class);
        new AllowanceInvalidRequest(
            allowanceNo: 'A001',
            invalidReason: str_repeat('一', 21),
        );
    }
}
