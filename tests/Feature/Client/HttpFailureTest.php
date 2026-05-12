<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Enums\Category;
use InvoicePorter\Ezpay\Enums\InvoiceStatus;
use InvoicePorter\Ezpay\Enums\PrintFlag;
use InvoicePorter\Ezpay\Enums\TaxType;
use InvoicePorter\Ezpay\Exceptions\EzpayTransportException;
use InvoicePorter\Ezpay\Requests\InvoiceIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\InvoiceItem;

final class HttpFailureTest extends ClientTestCase
{
    public function testServer5xxRaisesTransportException(): void
    {
        $client = $this->buildClient([new Response(503, [], 'maintenance')]);

        $this->expectException(EzpayTransportException::class);
        $client->issue($this->buildRequest());
    }

    public function testInvalidJsonRaisesTransportException(): void
    {
        $client = $this->buildClient([new Response(200, [], 'not-json')]);

        $this->expectException(EzpayTransportException::class);
        $client->issue($this->buildRequest());
    }

    private function buildRequest(): InvoiceIssueRequest
    {
        return new InvoiceIssueRequest(
            status: InvoiceStatus::Immediate,
            merchantOrderNo: 'ORD20260101',
            category: Category::B2C,
            taxType: TaxType::Taxable,
            amount: 476,
            taxAmount: 24,
            totalAmount: 500,
            items: [new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500)],
            printFlag: PrintFlag::No,
            loveCode: '13994',
        );
    }
}
