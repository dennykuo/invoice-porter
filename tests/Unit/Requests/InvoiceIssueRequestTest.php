<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests;

use InvoicePorter\Ezpay\Enums\Category;
use InvoicePorter\Ezpay\Enums\InvoiceStatus;
use InvoicePorter\Ezpay\Enums\PrintFlag;
use InvoicePorter\Ezpay\Enums\TaxType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\InvoiceIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\InvoiceItem;
use InvoicePorter\Ezpay\Responses\InvoiceIssueResponse;
use PHPUnit\Framework\TestCase;

final class InvoiceIssueRequestTest extends TestCase
{
    public function testB2cMinimalIsValid(): void
    {
        $request = new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2C,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [
                new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500),
            ],
            printFlag: PrintFlag::No,
        );

        $this->assertSame('invoice_issue', $request->uri());
        $this->assertSame('1.5', $request->version());
        $this->assertSame(InvoiceIssueResponse::class, $request->responseClass());
        $this->assertContains('MerchantID', $request->checkCodeFields() ?? []);
    }

    public function testPayloadContainsRequiredKeys(): void
    {
        $request = new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2B,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [
                new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 476, amount: 476),
                new InvoiceItem(name: '商品二', count: 2, unit: '個', price: 100, amount: 200),
            ],
            printFlag: PrintFlag::Yes,
            buyerName: '王大品',
            buyerUbn: '54352706',
            buyerEmail: 'buyer@example.com',
        );

        $payload = $request->toEncryptablePayload();

        $this->assertSame('1.5', $payload['Version']);
        $this->assertSame('JSON', $payload['RespondType']);
        $this->assertSame('ORD20260101', $payload['MerchantOrderNo']);
        $this->assertSame('B2B', $payload['Category']);
        $this->assertSame('1', $payload['Status']);
        $this->assertSame('Y', $payload['PrintFlag']);
        $this->assertSame('商品一|商品二', $payload['ItemName']);
        $this->assertSame('1|2', $payload['ItemCount']);
        $this->assertSame('500', $payload['TotalAmt']);
        $this->assertSame('54352706', $payload['BuyerUBN']);
    }

    public function testB2bRequiresBuyerUbn(): void
    {
        $this->expectException(EzpayValidationException::class);

        new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2B,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500)],
        );
    }

    public function testScheduledStatusRequiresCreateStatusTime(): void
    {
        $this->expectException(EzpayValidationException::class);

        new InvoiceIssueRequest(
            status: InvoiceStatus::Scheduled,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2C,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500)],
        );
    }

    public function testRejectsEmptyItems(): void
    {
        $this->expectException(EzpayValidationException::class);

        new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2C,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [],
        );
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(EzpayValidationException::class);

        new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2C,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500)],
            buyerEmail: 'not-an-email',
        );
    }

    public function testRejectsLongMerchantOrderNo(): void
    {
        $this->expectException(EzpayValidationException::class);

        new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: str_repeat('a', 31),
            category: Category::B2C,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500)],
        );
    }
}
