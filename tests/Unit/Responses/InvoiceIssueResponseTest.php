<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Responses;

use InvoicePorter\Ezpay\Enums\DisplayFlag;
use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Responses\InvoiceIssueResponse;
use PHPUnit\Framework\TestCase;

final class InvoiceIssueResponseTest extends TestCase
{
    public function testCreateTimeAtParsesValidString(): void
    {
        $response = new InvoiceIssueResponse([], [
            'CreateTime' => '2026-01-01 12:00:00',
        ]);

        $dt = $response->createTimeAt();
        $this->assertNotNull($dt);
        $this->assertSame('2026-01-01 12:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testCreateTimeAtReturnsNullWhenMissing(): void
    {
        $response = new InvoiceIssueResponse([], []);
        $this->assertNull($response->createTimeAt());
    }

    public function testToSearchRequestBuildsValidRequest(): void
    {
        $response = new InvoiceIssueResponse([], [
            'MerchantID' => '00000000',
            'MerchantOrderNo' => 'ORD20260101',
            'InvoiceNumber' => 'AA00000076',
            'RandomNum' => '0991',
            'TotalAmt' => '500',
        ]);

        $request = $response->toSearchRequest();

        $this->assertSame(SearchType::ByInvoiceNumber, $request->searchType);
        $this->assertSame(DisplayFlag::Redirect, $request->displayFlag);
        $this->assertSame('ORD20260101', $request->merchantOrderNo);
        $this->assertSame('AA00000076', $request->invoiceNumber);
        $this->assertSame('0991', $request->randomNum);
        $this->assertSame(500.0, $request->totalAmount);
    }

    public function testToSearchRequestThrowsWhenInvoiceNumberMissing(): void
    {
        $response = new InvoiceIssueResponse([], [
            'MerchantOrderNo' => 'ORD20260101',
            'RandomNum' => '0991',
            'TotalAmt' => '500',
        ]);

        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('invoiceNumber 或 randomNum');
        $response->toSearchRequest();
    }

    public function testToSearchRequestThrowsWhenRandomNumMissing(): void
    {
        $response = new InvoiceIssueResponse([], [
            'MerchantOrderNo' => 'ORD20260101',
            'InvoiceNumber' => 'AA00000076',
            'TotalAmt' => '500',
        ]);

        $this->expectException(EzpayValidationException::class);
        $response->toSearchRequest();
    }

    public function testToSearchRequestPassesNullTotalAmount(): void
    {
        $response = new InvoiceIssueResponse([], [
            'MerchantOrderNo' => 'ORD20260101',
            'InvoiceNumber' => 'AA00000076',
            'RandomNum' => '0991',
            // TotalAmt 缺
        ]);

        $request = $response->toSearchRequest();
        $this->assertNull($request->totalAmount);
    }
}
