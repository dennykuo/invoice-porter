<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests;

use InvoicePorter\Ezpay\Enums\DisplayFlag;
use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\InvoiceSearchRequest;
use PHPUnit\Framework\TestCase;

final class InvoiceSearchRequestTest extends TestCase
{
    public function testSearchByInvoiceNumberRequiresFields(): void
    {
        $this->expectException(EzpayValidationException::class);
        new InvoiceSearchRequest(
            searchType: SearchType::ByInvoiceNumber,
            merchantOrderNo: 'ORD',
        );
    }

    public function testSearchByMerchantOrderNoOnly(): void
    {
        $request = new InvoiceSearchRequest(
            searchType: SearchType::ByMerchantOrderNo,
            merchantOrderNo: 'ORD20260101',
        );

        $this->assertSame('1.3', $request->version());
        $payload = $request->toEncryptablePayload();
        $this->assertSame('1', $payload['SearchType']);
        $this->assertSame('ORD20260101', $payload['MerchantOrderNo']);
        $this->assertArrayNotHasKey('DisplayFlag', $payload);
    }

    public function testRedirectFlagSkipsCheckCode(): void
    {
        $request = new InvoiceSearchRequest(
            searchType: SearchType::ByInvoiceNumber,
            merchantOrderNo: 'ORD',
            invoiceNumber: 'AA00000076',
            randomNum: '0991',
            displayFlag: DisplayFlag::Redirect,
        );

        $this->assertNull($request->checkCodeFields());
        $payload = $request->toEncryptablePayload();
        $this->assertSame('1', $payload['DisplayFlag']);
    }

    public function testResultUrlFlagStillVerifiesCheckCode(): void
    {
        $request = new InvoiceSearchRequest(
            searchType: SearchType::ByInvoiceNumber,
            merchantOrderNo: 'ORD',
            invoiceNumber: 'AA00000076',
            randomNum: '0991',
            displayFlag: DisplayFlag::ResultUrl,
        );

        $this->assertNotNull($request->checkCodeFields());
    }

    public function testRejectsHyphenInMerchantOrderNo(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('英文、數字、底線');

        new InvoiceSearchRequest(
            searchType: SearchType::ByMerchantOrderNo,
            merchantOrderNo: 'ORD-001',
        );
    }
}
