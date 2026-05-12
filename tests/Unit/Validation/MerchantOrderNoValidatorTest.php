<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Validation;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Validation\MerchantOrderNoValidator;
use PHPUnit\Framework\TestCase;

final class MerchantOrderNoValidatorTest extends TestCase
{
    public function testAcceptsAlphanumericAndUnderscoreWithinMaxLength(): void
    {
        // 20 字元邊界 OK
        MerchantOrderNoValidator::assert(str_repeat('A', 20));
        MerchantOrderNoValidator::assert('ORD_20260512_001');
        MerchantOrderNoValidator::assert('a1_B2');

        $this->expectNotToPerformAssertions();
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('merchantOrderNo 不可為空');

        MerchantOrderNoValidator::assert('');
    }

    public function testRejectsLengthOver20(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('merchantOrderNo 長度不可超過 20');

        MerchantOrderNoValidator::assert(str_repeat('A', 21));
    }

    public function testRejectsHyphenAndReportsViolatingChars(): void
    {
        try {
            MerchantOrderNoValidator::assert('ORD-20260512-001');
            $this->fail('Expected EzpayValidationException');
        } catch (EzpayValidationException $e) {
            $this->assertStringContainsString('英文、數字、底線', $e->getMessage());
            $this->assertStringContainsString('-', $e->getMessage());
        }
    }

    public function testRejectsChineseCharacters(): void
    {
        try {
            MerchantOrderNoValidator::assert('訂單001');
            $this->fail('Expected EzpayValidationException');
        } catch (EzpayValidationException $e) {
            $this->assertStringContainsString('訂', $e->getMessage());
        }
    }

    public function testRejectsSpaceAndReportsIt(): void
    {
        try {
            MerchantOrderNoValidator::assert('ORD 001');
            $this->fail('Expected EzpayValidationException');
        } catch (EzpayValidationException $e) {
            // 空白字元應在訊息中以可讀方式呈現
            $this->assertStringContainsString('英文、數字、底線', $e->getMessage());
        }
    }

    public function testMaxLengthConstantIs20(): void
    {
        $this->assertSame(20, MerchantOrderNoValidator::MAX_LENGTH);
    }
}
