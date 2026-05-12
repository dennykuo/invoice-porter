<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Validation;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Validation\BuyerEmailValidator;
use PHPUnit\Framework\TestCase;

final class BuyerEmailValidatorTest extends TestCase
{
    public function testAcceptsNull(): void
    {
        BuyerEmailValidator::assert(null);

        $this->expectNotToPerformAssertions();
    }

    public function testAcceptsEmptyString(): void
    {
        BuyerEmailValidator::assert('');

        $this->expectNotToPerformAssertions();
    }

    public function testAcceptsValidEmail(): void
    {
        BuyerEmailValidator::assert('buyer@example.com');

        $this->expectNotToPerformAssertions();
    }

    public function testRejectsInvalidFormat(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('buyerEmail 格式錯誤');

        BuyerEmailValidator::assert('not-an-email');
    }

    public function testRejectsOver80Chars(): void
    {
        // local part 60 字（RFC 5321 上限 64）+ '@' + domain = 82 字元
        $tooLong = str_repeat('a', 60) . '@' . str_repeat('b', 17) . '.com';
        $this->assertGreaterThan(80, strlen($tooLong));
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('80');

        BuyerEmailValidator::assert($tooLong);
    }

    public function testMaxLengthConstantIs80(): void
    {
        $this->assertSame(80, BuyerEmailValidator::MAX_LENGTH);
    }
}
