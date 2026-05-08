<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\InvoiceInvalidRequest;
use PHPUnit\Framework\TestCase;

final class InvoiceInvalidRequestTest extends TestCase
{
    public function testCheckCodeFieldsAreNullWithoutHints(): void
    {
        $request = new InvoiceInvalidRequest(
            invoiceNumber: 'AA00000076',
            invalidReason: '訂單取消',
        );

        $this->assertNull($request->checkCodeFields(), '未帶入 randomNum 等資料時不驗 CheckCode');
        $this->assertSame([], $request->checkCodeHint());
    }

    public function testCheckCodeFieldsActiveWhenAllHintsProvided(): void
    {
        $request = new InvoiceInvalidRequest(
            invoiceNumber: 'AA00000076',
            invalidReason: '訂單取消',
            randomNum: '0991',
            invoiceTransNo: '20051309002377869',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 500,
        );

        $fields = $request->checkCodeFields();
        $this->assertNotNull($fields);
        $this->assertContains('RandomNum', $fields);

        $hint = $request->checkCodeHint();
        $this->assertSame('0991', $hint['RandomNum']);
        $this->assertSame('500', $hint['TotalAmt']);
    }

    public function testRejectsLongInvalidReason(): void
    {
        $this->expectException(EzpayValidationException::class);
        new InvoiceInvalidRequest(
            invoiceNumber: 'AA00000076',
            invalidReason: str_repeat('一', 21),
        );
    }

    public function testRejectsEmptyInvalidReason(): void
    {
        $this->expectException(EzpayValidationException::class);
        new InvoiceInvalidRequest(
            invoiceNumber: 'AA00000076',
            invalidReason: '',
        );
    }
}
