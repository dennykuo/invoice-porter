<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Exceptions;

use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use PHPUnit\Framework\TestCase;

final class EzpayApiExceptionTest extends TestCase
{
    public function testErrorCodePrefixExtractsLeadingLettersAndThreeDigits(): void
    {
        $this->assertSame('INV100', $this->make('INV10014')->errorCodePrefix());
        $this->assertSame('INV700', $this->make('INV70001')->errorCodePrefix());
        $this->assertSame('LIB100', $this->make('LIB10005')->errorCodePrefix());
        $this->assertSame('KEY100', $this->make('KEY10002')->errorCodePrefix());
    }

    public function testErrorCodePrefixReturnsEmptyForMalformedCode(): void
    {
        $this->assertSame('', $this->make('weird-code')->errorCodePrefix());
        $this->assertSame('', $this->make('')->errorCodePrefix());
    }

    public function testIsFieldFormatErrorMatchesInv100AndInv700(): void
    {
        $this->assertTrue($this->make('INV10001')->isFieldFormatError());
        $this->assertTrue($this->make('INV10014')->isFieldFormatError());
        $this->assertTrue($this->make('INV70001')->isFieldFormatError());

        $this->assertFalse($this->make('LIB10005')->isFieldFormatError());
        $this->assertFalse($this->make('NOR10001')->isFieldFormatError());
    }

    public function testIsAuthErrorMatchesInv900AndKey100(): void
    {
        $this->assertTrue($this->make('INV90011')->isAuthError());
        $this->assertTrue($this->make('INV90012')->isAuthError());
        $this->assertTrue($this->make('KEY10002')->isAuthError());

        $this->assertFalse($this->make('INV10001')->isAuthError());
        $this->assertFalse($this->make('LIB10005')->isAuthError());
    }

    public function testIsDuplicateOrderNoMatchesKnownCodes(): void
    {
        // 藍新對「訂單編號重複」可能以 NOR10001（新版）或 LIB10003（舊文件）回應
        $this->assertTrue($this->make('NOR10001')->isDuplicateOrderNo());
        $this->assertTrue($this->make('LIB10003')->isDuplicateOrderNo());

        $this->assertFalse($this->make('LIB10005')->isDuplicateOrderNo());
        $this->assertFalse($this->make('INV10001')->isDuplicateOrderNo());
    }

    private function make(string $code): EzpayApiException
    {
        return new EzpayApiException($code, 'msg', ['Status' => $code]);
    }
}
