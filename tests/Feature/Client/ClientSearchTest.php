<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Crypto\AesCryptor;
use InvoicePorter\Ezpay\Enums\DisplayFlag;
use InvoicePorter\Ezpay\Enums\InvoiceLifecycleStatus;
use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\InvoiceSearchRequest;

final class ClientSearchTest extends ClientTestCase
{
    public function testSearchSuccessParsesLifecycleStatus(): void
    {
        $body = $this->makeEnvelope('SUCCESS', 'OK', [
            'MerchantID' => self::MERCHANT_ID,
            'InvoiceTransNo' => 'IT004',
            'MerchantOrderNo' => 'ORD20260101',
            'TotalAmt' => '500',
            'RandomNum' => '0991',
            'InvoiceNumber' => 'AA00000076',
            'InvoiceStatus' => '1',
        ], signCheckCode: true);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $response = $client->search(new InvoiceSearchRequest(
            searchType: SearchType::ByInvoiceNumber,
            merchantOrderNo: 'ORD20260101',
            invoiceNumber: 'AA00000076',
            randomNum: '0991',
        ));

        $this->assertSame(InvoiceLifecycleStatus::Issued, $response->lifecycleStatus());
        $this->assertSame('AA00000076', $response->invoiceNumber());
    }

    public function testSearchRedirectHtmlContainsEncryptedPayload(): void
    {
        $client = $this->buildClient([]);

        $request = new InvoiceSearchRequest(
            searchType: SearchType::ByInvoiceNumber,
            merchantOrderNo: 'ORD20260101',
            invoiceNumber: 'AA00000076',
            randomNum: '0991',
            displayFlag: DisplayFlag::Redirect,
        );

        $html = $client->searchRedirectHtml($request);
        $this->assertStringContainsString('action="https://cinv.ezpay.com.tw/Api/invoice_search"', $html);
        $this->assertStringContainsString('name="MerchantID_" value="' . self::MERCHANT_ID . '"', $html);

        // 抽出 PostData_ 解密回頭驗證內容
        preg_match('/name="PostData_" value="([0-9a-f]+)"/', $html, $matches);
        $this->assertNotEmpty($matches);
        $cryptor = new AesCryptor(self::HASH_KEY, self::HASH_IV);
        parse_str($cryptor->decrypt($matches[1]), $decoded);

        /** @var array<string,string> $decoded */
        $this->assertSame('1', $decoded['DisplayFlag']);
        $this->assertSame('AA00000076', $decoded['InvoiceNumber']);
    }

    public function testPublicQueryRedirectHtmlBuildsAutoSubmitForm(): void
    {
        $client = $this->buildClient([]);

        $html = $client->publicQueryRedirectHtml(
            invoiceNumber: 'AA00000076',
            randomNum: '0991',
            merchantOrderNo: 'ORD20260101',
            totalAmount: 500,
        );

        $this->assertStringContainsString('action="https://cinv.ezpay.com.tw/Api/invoice_search"', $html);
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('document.getElementById', $html);

        // 解出 PostData_ 並驗證 SearchType / DisplayFlag / 必要欄位
        preg_match('/name="PostData_" value="([0-9a-f]+)"/', $html, $matches);
        $this->assertNotEmpty($matches);
        $cryptor = new AesCryptor(self::HASH_KEY, self::HASH_IV);
        parse_str($cryptor->decrypt($matches[1]), $decoded);

        /** @var array<string,string> $decoded */
        $this->assertSame('0', $decoded['SearchType']);
        $this->assertSame('1', $decoded['DisplayFlag']);
        $this->assertSame('AA00000076', $decoded['InvoiceNumber']);
        $this->assertSame('0991', $decoded['RandomNum']);
        $this->assertSame('ORD20260101', $decoded['MerchantOrderNo']);
        $this->assertSame('500', $decoded['TotalAmt']);
    }

    public function testPublicQueryRedirectHtmlRejectsHtmlSpecialCharsInMerchantOrderNo(): void
    {
        // 0.4.1 起 MerchantOrderNoValidator 強制 [A-Za-z0-9_]，HTML 特殊字元在建構 Request
        // 階段就被擋下，永遠無法到達 encryption / outer HTML。比起「加密後不留明文」這是更強的
        // 安全保證 — 連加密層的攻擊面都關閉了。
        $this->expectException(EzpayValidationException::class);

        $this->buildClient([])->publicQueryRedirectHtml(
            invoiceNumber: 'AA00000076',
            randomNum: '0991',
            merchantOrderNo: 'ORD<&"\'>20260101',
            totalAmount: 500,
        );
    }

    public function testSearchResultUrlFlagStillReturnsResponse(): void
    {
        $body = $this->makeEnvelope('SUCCESS', 'OK', [
            'MerchantID' => self::MERCHANT_ID,
            'InvoiceTransNo' => 'IT005',
            'MerchantOrderNo' => 'ORD20260101',
            'TotalAmt' => '500',
            'RandomNum' => '0991',
            'InvoiceNumber' => 'AA00000076',
            'InvoiceURL' => 'https://example.com/result',
        ], signCheckCode: true);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $request = new InvoiceSearchRequest(
            searchType: SearchType::ByInvoiceNumber,
            merchantOrderNo: 'ORD20260101',
            invoiceNumber: 'AA00000076',
            randomNum: '0991',
            displayFlag: DisplayFlag::ResultUrl,
        );

        $response = $client->search($request);
        $this->assertSame('https://example.com/result', $response->searchResultUrl());

        $sent = $this->decryptLastRequest();
        $this->assertSame('2', $sent['postData']['DisplayFlag']);
    }
}
