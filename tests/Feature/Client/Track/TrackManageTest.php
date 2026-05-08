<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client\Track;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayCheckCodeException;
use InvoicePorter\Ezpay\Requests\Track\TrackManageRequest;

final class TrackManageTest extends TrackClientTestCase
{
    public function testManageSuccessSkipsCheckCodeByDefault(): void
    {
        // 預設 expectCheckCode=false，response 沒有 CheckCode 也不會炸
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '管理成功',
            'Result' => json_encode([
                'ManagementNo' => 'MN-001',
                'AphabeticLetter' => 'AB',
                'StartNumber' => '00000000',
                'EndNumber' => '00000049',
                'Type' => '07',
                'Flag' => '2',
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->trackManage(new TrackManageRequest(
            managementNo: 'MN-001',
            flag: TrackFlag::Disabled,
        ));

        $this->assertSame('MN-001', $response->managementNo());
        $this->assertSame(TrackFlag::Disabled, $response->flag());

        $sent = $this->decryptLastRequest();
        $this->assertSame('manageNumber', basename($this->getLastRequestUri()));
        $this->assertSame('MN-001', $sent['postData']['ManagementNo']);
        $this->assertSame('2', $sent['postData']['Flag']);
    }

    public function testExpectCheckCodeVerifiesSignature(): void
    {
        $body = $this->makeEnvelope('SUCCESS', '管理成功', [
            'ManagementNo' => 'MN-001',
            'AphabeticLetter' => 'AB',
            'StartNumber' => '00000000',
            'EndNumber' => '00000049',
            'Type' => '07',
            'Flag' => '0',
        ], signCheckCode: true);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->trackManage(new TrackManageRequest(
            managementNo: 'MN-001',
            flag: TrackFlag::Paused,
            expectCheckCode: true,
        ));

        $this->assertSame(TrackFlag::Paused, $response->flag());
    }

    public function testExpectCheckCodeWithBadSignatureFails(): void
    {
        // 開啟驗證但 CheckCode 錯誤
        $body = json_encode([
            'Status' => 'SUCCESS',
            'Message' => '',
            'Result' => json_encode([
                'ManagementNo' => 'MN-001',
                'AphabeticLetter' => 'AB',
                'StartNumber' => '00000000',
                'EndNumber' => '00000049',
                'CheckCode' => str_repeat('X', 64),
            ], JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $this->expectException(EzpayCheckCodeException::class);
        $client->trackManage(new TrackManageRequest(
            managementNo: 'MN-001',
            flag: TrackFlag::Disabled,
            expectCheckCode: true,
        ));
    }
}
