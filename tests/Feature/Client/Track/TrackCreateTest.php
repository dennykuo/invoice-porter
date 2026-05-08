<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client\Track;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\InvoiceType;
use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayCheckCodeException;
use InvoicePorter\Ezpay\Requests\Track\TrackCreateRequest;

final class TrackCreateTest extends TrackClientTestCase
{
    public function testCreateSuccessReturnsTypedResponse(): void
    {
        $body = $this->makeEnvelope('SUCCESS', '新增字軌成功', [
            'ManagementNo' => 'MN-001',
            'AphabeticLetter' => 'AB',
            'StartNumber' => '00000000',
            'EndNumber' => '00000049',
            'Type' => '07',
            'LastNumber' => '00000000',
            'Flag' => '1',
            'CreateDatetime' => '2026-05-01 10:00:00',
        ], signCheckCode: true);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->trackCreate($this->buildRequest());

        $this->assertSame('SUCCESS', $response->status());
        $this->assertSame('MN-001', $response->managementNo());
        $this->assertSame('AB', $response->aphabeticLetter());
        $this->assertSame(InvoiceType::General, $response->type());
        $this->assertSame(TrackFlag::Active, $response->flag());

        $sent = $this->decryptLastRequest();
        $this->assertSame(self::COMPANY_ID, $sent['companyId']);
        $this->assertSame('1.0', $sent['postData']['Version']);
        $this->assertSame('createNumber', basename($this->getLastRequestUri()));
        $this->assertSame('115', $sent['postData']['Year']);
        $this->assertSame('AB', $sent['postData']['AphabeticLetter']);
    }

    public function testApiErrorRaisesEzpayApiException(): void
    {
        $body = json_encode([
            'Status' => 'INM10001',
            'Message' => '字軌已存在',
            'Result' => '',
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        try {
            $client->trackCreate($this->buildRequest());
            $this->fail('預期應拋出 EzpayApiException');
        } catch (EzpayApiException $e) {
            $this->assertSame('INM10001', $e->errorCode);
            $this->assertSame('字軌已存在', $e->getMessage());
        }
    }

    public function testCheckCodeMismatchRaisesException(): void
    {
        // 結構正確但寫死錯誤的 CheckCode
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '',
            'Result' => json_encode([
                'ManagementNo' => 'MN-001',
                'AphabeticLetter' => 'AB',
                'StartNumber' => '00000000',
                'EndNumber' => '00000049',
                'Type' => '07',
                'CheckCode' => str_repeat('X', 64),
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $this->expectException(EzpayCheckCodeException::class);
        $client->trackCreate($this->buildRequest());
    }

    public function testMissingCheckCodeRaisesException(): void
    {
        // SUCCESS 但 Result 沒有 CheckCode 欄位
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '',
            'Result' => json_encode([
                'ManagementNo' => 'MN-001',
                'AphabeticLetter' => 'AB',
                'StartNumber' => '00000000',
                'EndNumber' => '00000049',
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $this->expectException(EzpayCheckCodeException::class);
        $client->trackCreate($this->buildRequest());
    }

    private function buildRequest(): TrackCreateRequest
    {
        return new TrackCreateRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            aphabeticLetter: 'AB',
            startNumber: '00000000',
            endNumber: '00000049',
        );
    }
}
