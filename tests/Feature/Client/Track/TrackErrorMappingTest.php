<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client\Track;

use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Requests\Track\TrackCreateRequest;

final class TrackErrorMappingTest extends TrackClientTestCase
{
    /**
     * EZP_Track_1.0.0 附錄三所列字軌相關錯誤碼。
     * 預期所有業務錯誤碼皆會包成 EzpayApiException，errorCode / message 完整保留。
     *
     * @return array<string,array{0:string,1:string}>
     */
    public static function errorCodes(): array
    {
        return [
            // INM = Invoice Number Management（字軌專屬）
            'INM10001 字軌已存在' => ['INM10001', '字軌已存在'],
            'INM10002 字軌號碼錯誤' => ['INM10002', '字軌號碼錯誤'],
            'INM10003 字軌期別錯誤' => ['INM10003', '字軌期別錯誤'],
            'INM10004 字軌起訖號碼錯誤' => ['INM10004', '字軌起訖號碼錯誤'],
            'INM10005 字軌已被使用' => ['INM10005', '字軌已被使用'],
            'INM10006 查無字軌資料' => ['INM10006', '查無字軌資料'],

            // MOD = 模組層級錯誤
            'MOD10001 系統忙碌中' => ['MOD10001', '系統忙碌中'],
            'MOD10003 系統異常' => ['MOD10003', '系統異常'],
            'MOD10006 系統發生錯誤' => ['MOD10006', '系統發生錯誤'],

            // LIB = 函式庫層級錯誤
            'LIB10001 必填欄位不齊' => ['LIB10001', '必填欄位不齊'],
            'LIB10004 參數格式錯誤' => ['LIB10004', '參數格式錯誤'],
            'LIB10007 商家不存在' => ['LIB10007', '商家不存在'],
            'LIB10010 公司不存在' => ['LIB10010', '公司不存在'],
            'LIB10011 公司認證失敗' => ['LIB10011', '公司認證失敗'],
            'LIB10013 簽章錯誤' => ['LIB10013', '簽章錯誤'],
            'LIB10016 資料格式錯誤' => ['LIB10016', '資料格式錯誤'],

            // SET / KEY = 認證層級錯誤
            'SET10006 設定錯誤' => ['SET10006', '設定錯誤'],
            'KEY10006 金鑰錯誤' => ['KEY10006', '金鑰錯誤'],
            'KEY10010 金鑰過期' => ['KEY10010', '金鑰過期'],
            'KEY10011 金鑰未啟用' => ['KEY10011', '金鑰未啟用'],
        ];
    }

    /**
     * @dataProvider errorCodes
     */
    public function testMapsErrorCodeToException(string $code, string $message): void
    {
        $body = json_encode([
            'Status' => $code,
            'Message' => $message,
            'Result' => '',
        ], JSON_THROW_ON_ERROR);

        $client = $this->buildClient([new Response(200, [], $body)]);

        $request = new TrackCreateRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            aphabeticLetter: 'AB',
            startNumber: '00000000',
            endNumber: '00000049',
        );

        try {
            $client->trackCreate($request);
            $this->fail('應拋出 EzpayApiException');
        } catch (EzpayApiException $e) {
            $this->assertSame($code, $e->errorCode);
            $this->assertSame($message, $e->getMessage());
        }
    }
}
