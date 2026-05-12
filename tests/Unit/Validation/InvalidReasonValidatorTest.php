<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Validation;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Validation\InvalidReasonValidator;
use PHPUnit\Framework\TestCase;

final class InvalidReasonValidatorTest extends TestCase
{
    public function testAcceptsAtMaxLength(): void
    {
        InvalidReasonValidator::assert(str_repeat('原', 20));

        $this->expectNotToPerformAssertions();
    }

    public function testAcceptsShortReason(): void
    {
        InvalidReasonValidator::assert('客戶取消');

        $this->expectNotToPerformAssertions();
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('invalidReason 不可為空');

        InvalidReasonValidator::assert('');
    }

    public function testRejectsOver20ChineseChars(): void
    {
        // 21 個中文（mb_strlen=21）應被擋下；若實作誤用 strlen 則 21*3=63 byte 也會擋下但訊息不同
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('長度不可超過 20');

        InvalidReasonValidator::assert(str_repeat('原', 21));
    }

    public function testMaxLengthConstantIs20(): void
    {
        $this->assertSame(20, InvalidReasonValidator::MAX_LENGTH);
    }
}
