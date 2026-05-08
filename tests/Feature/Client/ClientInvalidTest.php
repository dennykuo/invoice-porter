<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Requests\InvoiceInvalidRequest;

final class ClientInvalidTest extends ClientTestCase
{
    public function testInvalidWithoutCheckCode(): void
    {
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '作廢成功',
            'Result' => json_encode([
                'MerchantID' => self::MERCHANT_ID,
                'InvoiceNumber' => 'AA00000076',
                'CreateTime' => '2026-01-02 11:11:11',
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->invalid(new InvoiceInvalidRequest('AA00000076', '訂單取消'));

        $this->assertSame('AA00000076', $response->invoiceNumber());
        $sent = $this->decryptLastRequest();
        $this->assertSame('invoice_invalid', basename($this->getLastRequestUri()));
        $this->assertSame('AA00000076', $sent['postData']['InvoiceNumber']);
    }

    public function testInvalidWithCheckCodeHints(): void
    {
        $body = $this->makeEnvelope('SUCCESS', '作廢成功', [
            'MerchantID' => self::MERCHANT_ID,
            'InvoiceNumber' => 'AA00000076',
            'InvoiceTransNo' => 'IT003',
            'MerchantOrderNo' => 'ORD20260101',
            'TotalAmt' => '500',
            'RandomNum' => '0991',
        ], signCheckCode: true);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->invalid(new InvoiceInvalidRequest(
            invoiceNumber: 'AA00000076',
            invalidReason: '訂單取消',
            randomNum: '0991',
            invoiceTransNo: 'IT003',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 500,
        ));

        $this->assertSame('AA00000076', $response->invoiceNumber());
    }
}
