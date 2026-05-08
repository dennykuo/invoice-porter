<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client\Track;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Requests\Track\TrackSearchRequest;

final class TrackSearchTest extends TrackClientTestCase
{
    public function testSearchSuccessReturnsList(): void
    {
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '查詢成功',
            'Result' => json_encode([
                'Data' => [
                    [
                        'ManagementNo' => 'MN-001',
                        'AphabeticLetter' => 'AB',
                        'StartNumber' => '00000000',
                        'EndNumber' => '00000049',
                        'Type' => '07',
                        'LastNumber' => '00000005',
                        'Flag' => '1',
                    ],
                    [
                        'ManagementNo' => 'MN-002',
                        'AphabeticLetter' => 'CD',
                        'StartNumber' => '00000050',
                        'EndNumber' => '00000099',
                        'Type' => '07',
                        'LastNumber' => '00000050',
                        'Flag' => '1',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->trackSearch(new TrackSearchRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            flag: TrackFlag::Active,
        ));

        $items = $response->items();
        $this->assertCount(2, $items);
        $this->assertSame('MN-001', $items[0]['ManagementNo']);
        $this->assertSame('MN-001', $response->firstManagementNo());

        $sent = $this->decryptLastRequest();
        $this->assertSame('searchNumber', basename($this->getLastRequestUri()));
        $this->assertSame('115', $sent['postData']['Year']);
        $this->assertSame('1', $sent['postData']['Term']);
        $this->assertSame('1', $sent['postData']['Flag']);
    }

    public function testSearchReturnsSingleObjectAsList(): void
    {
        // 藍新可能直接把單筆物件放在 Result 而不是包陣列
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '',
            'Result' => json_encode([
                'ManagementNo' => 'MN-001',
                'AphabeticLetter' => 'AB',
                'StartNumber' => '00000000',
                'EndNumber' => '00000049',
                'Type' => '07',
                'Flag' => '1',
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->trackSearch(new TrackSearchRequest(year: '115', term: InvoiceTerm::JanFeb));

        $this->assertSame('MN-001', $response->firstManagementNo());
        $this->assertCount(1, $response->items());
    }

    public function testApiErrorRaisesEzpayApiException(): void
    {
        $body = json_encode([
            'Status' => 'INM10006',
            'Message' => '查無資料',
            'Result' => '',
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        try {
            $client->trackSearch(new TrackSearchRequest(year: '115', term: InvoiceTerm::JanFeb));
            $this->fail('預期應拋出 EzpayApiException');
        } catch (EzpayApiException $e) {
            $this->assertSame('INM10006', $e->errorCode);
        }
    }
}
