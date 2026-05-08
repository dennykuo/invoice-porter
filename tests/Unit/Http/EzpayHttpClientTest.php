<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Exceptions\EzpayTransportException;
use InvoicePorter\Ezpay\Http\EzpayHttpClient;
use PHPUnit\Framework\TestCase;

final class EzpayHttpClientTest extends TestCase
{
    public function testReturnsDecodedJson(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['Status' => 'SUCCESS', 'Message' => 'ok'], JSON_THROW_ON_ERROR)),
        ]);
        $client = new EzpayHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $result = $client->postForm('http://test.local/x', ['a' => '1']);

        $this->assertSame('SUCCESS', $result['Status']);
    }

    public function testThrowsOn5xx(): void
    {
        $mock = new MockHandler([new Response(500, [], 'fail')]);
        $client = new EzpayHttpClient(new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]));

        $this->expectException(EzpayTransportException::class);
        $client->postForm('http://test.local/x', []);
    }

    public function testThrowsOnInvalidJson(): void
    {
        $mock = new MockHandler([new Response(200, [], 'not-json')]);
        $client = new EzpayHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $this->expectException(EzpayTransportException::class);
        $client->postForm('http://test.local/x', []);
    }

    public function testThrowsOnNonObjectJson(): void
    {
        $mock = new MockHandler([new Response(200, [], '"plain string"')]);
        $client = new EzpayHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $this->expectException(EzpayTransportException::class);
        $client->postForm('http://test.local/x', []);
    }
}
