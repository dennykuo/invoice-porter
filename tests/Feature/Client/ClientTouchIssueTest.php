<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Requests\InvoiceTouchIssueRequest;

final class ClientTouchIssueTest extends ClientTestCase
{
    public function testTouchIssueSuccess(): void
    {
        $body = $this->makeEnvelope('SUCCESS', 'OK', [
            'MerchantID' => self::MERCHANT_ID,
            'InvoiceTransNo' => 'IT002',
            'MerchantOrderNo' => 'ORD20260101',
            'TotalAmt' => '500',
            'RandomNum' => '1234',
            'InvoiceNumber' => 'AA00000077',
        ], signCheckCode: true);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->touchIssue(new InvoiceTouchIssueRequest('ORD20260101', 500));

        $this->assertSame('AA00000077', $response->invoiceNumber());
        $sent = $this->decryptLastRequest();
        $this->assertSame('invoice_touch_issue', basename($this->getLastRequestUri()));
        $this->assertSame('1.0', $sent['postData']['Version']);
    }
}
