<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests;

use InvoicePorter\Ezpay\Enums\CarrierType;
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
    /** 預設合規 InvoiceItem，多數測試只需一個品項即可達成驗證。 */
    private function defaultItem(): InvoiceItem
    {
        return new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500);
    }

    /**
     * 提供合規最小欄位，個別測試只 override 想驗的欄位。
     *
     * 預設場景：B2C + 應稅 + PrintFlag::Yes（紙本，不需 carrier/loveCode），最少欄位即可建構。
     *
     * @return array<string, mixed>
     */
    private function validBaseArgs(): array
    {
        return [
            'status' => InvoiceStatus::Immediate,
            'merchantOrderNo' => 'ORD20260101',
            'category' => Category::B2C,
            'taxType' => TaxType::Taxable,
            'amount' => 476,
            'taxAmount' => 24,
            'totalAmount' => 500,
            'items' => [$this->defaultItem()],
            'printFlag' => PrintFlag::Yes,
        ];
    }

    public function testB2cMinimalIsValid(): void
    {
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'loveCode' => '13994',
        ]);

        $this->assertSame('invoice_issue', $request->uri());
        $this->assertSame('1.5', $request->version());
        $this->assertSame(InvoiceIssueResponse::class, $request->responseClass());
        $this->assertContains('MerchantID', $request->checkCodeFields() ?? []);
    }

    public function testPayloadContainsRequiredKeys(): void
    {
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'category' => Category::B2B,
            'items' => [
                new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 476, amount: 476),
                new InvoiceItem(name: '商品二', count: 2, unit: '個', price: 100, amount: 200),
            ],
            'buyerName' => '王大品',
            'buyerUbn' => '54352706',
            'buyerEmail' => 'buyer@example.com',
        ]);

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
        $this->expectExceptionMessage('B2B 發票必須提供 buyerUbn');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'category' => Category::B2B]);
    }

    public function testScheduledStatusRequiresCreateStatusTime(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('createStatusTime');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'status' => InvoiceStatus::Scheduled]);
    }

    public function testRejectsEmptyItems(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('items 不可為空');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'items' => []]);
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('buyerEmail 格式錯誤');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'buyerEmail' => 'not-an-email']);
    }

    public function testRejectsLongMerchantOrderNo(): void
    {
        // 藍新規格 Varchar(20)，21 字元應被擋下
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('長度不可超過 20');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'merchantOrderNo' => str_repeat('a', 21),
        ]);
    }

    public function testRejectsHyphenInMerchantOrderNo(): void
    {
        // 藍新規格僅允許英、數、底線，連字號會被遠端打回 INV70001 / INV10014
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('英文、數字、底線');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'merchantOrderNo' => 'ORD-20260512-001',
        ]);
    }

    public function testB2cTaxablePrintFlagNoWithoutCarrierOrLoveCodeThrows(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('B2C + printFlag=N');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'printFlag' => PrintFlag::No]);
    }

    public function testB2cTaxablePrintFlagNoWithMobileCarrierPasses(): void
    {
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::Mobile,
            'carrierNum' => '/ABC1234',
        ]);

        $this->assertSame(CarrierType::Mobile, $request->carrierType);
        // Round-trip：carrier 真的進到 payload 而非只賦值給 property
        $this->assertSame('1', $request->toEncryptablePayload()['CarrierType']);
        $this->assertSame('/ABC1234', $request->toEncryptablePayload()['CarrierNum']);
    }

    public function testB2cTaxablePrintFlagNoWithLoveCodePasses(): void
    {
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'loveCode' => '13994',
        ]);

        $this->assertSame('13994', $request->loveCode);
        $this->assertSame('13994', $request->toEncryptablePayload()['LoveCode']);
    }

    public function testB2cTaxablePrintFlagYesNeedsNothing(): void
    {
        // validBaseArgs 預設即 PrintFlag::Yes，這個 case 就是 base 本身的正例
        $request = new InvoiceIssueRequest(...$this->validBaseArgs());

        $this->assertSame(PrintFlag::Yes, $request->printFlag);
    }

    public function testB2bTaxablePrintFlagNoIsAllowed(): void
    {
        // 範圍只擋 B2C，B2B 放行（B2B 走統編寄送，本來就不需 carrier）
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'category' => Category::B2B,
            'printFlag' => PrintFlag::No,
            'buyerUbn' => '12345678',
        ]);

        $this->assertSame(Category::B2B, $request->category);
    }

    public function testCarrierTypeSetButCarrierNumEmptyThrowsExplicitPairError(): void
    {
        // 0.5.0 起 carrierType 設了但 carrierNum 為空 → 直接拋 explicit pair error
        // （過去 0.4.x 會 silent treat as missing，最終可能打到 missing-carrier-or-lovecode）
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('carrierType 與 carrierNum');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::Mobile,
            'carrierNum' => '',
        ]);
    }

    // ---------- P1：欄位長度 / 格式（貼齊 EZP_INVI_1.2.2 規格）----------

    public function testRejectsBuyerNameOver60(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('buyerName');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'buyerName' => str_repeat('王', 61)]);
    }

    public function testRejectsBuyerAddressOver100(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('buyerAddress');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'buyerAddress' => str_repeat('A', 101)]);
    }

    public function testRejectsBuyerEmailOver80(): void
    {
        // local part 60 字（RFC 5321 上限 64）+ '@' + domain 21 字 = 82 字元
        $tooLong = str_repeat('a', 60) . '@' . str_repeat('b', 17) . '.com';
        $this->assertGreaterThan(80, strlen($tooLong));
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('80');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'buyerEmail' => $tooLong]);
    }

    public function testRejectsCommentOver200(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('comment');

        new InvoiceIssueRequest(...[...$this->validBaseArgs(), 'comment' => str_repeat('A', 201)]);
    }

    public function testRejectsBuyerUbnNon8Digits(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('buyerUbn');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'category' => Category::B2B,
            'buyerUbn' => '1234567', // 7 碼
        ]);
    }

    public function testRejectsBuyerUbnContainsLetters(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('buyerUbn');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'category' => Category::B2B,
            'buyerUbn' => '1234567A', // 含字母
        ]);
    }

    public function testRejectsLoveCodeNon3to7Digits(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('loveCode');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'loveCode' => '12', // 2 碼太短
        ]);
    }

    public function testRejectsLoveCodeWithLetters(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('loveCode');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'loveCode' => '12X4',
        ]);
    }

    public function testRejectsInvalidMobileCarrierNum(): void
    {
        // 手機條碼必須 / 開頭 + 後 7 碼 [A-Z0-9.\-+]
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('carrierNum');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::Mobile,
            'carrierNum' => 'ABC1234', // 缺前導 /
        ]);
    }

    public function testAcceptsValidMobileCarrierNum(): void
    {
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::Mobile,
            'carrierNum' => '/ABC1234',
        ]);

        $this->assertSame('/ABC1234', $request->carrierNum);
    }

    public function testRejectsInvalidCdcCarrierNum(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('carrierNum');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::CitizenDigitalCertificate,
            'carrierNum' => 'XX1234', // 不足 14 碼數字
        ]);
    }

    public function testAcceptsValidCdcCarrierNum(): void
    {
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::CitizenDigitalCertificate,
            'carrierNum' => 'AB12345678901234',
        ]);

        $this->assertSame('AB12345678901234', $request->carrierNum);
    }

    public function testAcceptsMemberCarrierNumFreeForm(): void
    {
        // 會員載具無固定規格（廠商自訂），SDK 不擋格式
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::Member,
            'carrierNum' => 'member-id-001',
        ]);

        $this->assertSame('member-id-001', $request->carrierNum);
    }

    public function testRejectsMemberCarrierNumTooLong(): void
    {
        // 但會員載具有 64 字元上限以避免異常輸入
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('carrierNum');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::Member,
            'carrierNum' => str_repeat('a', 65),
        ]);
    }

    // ---------- 0.5.0：cross-field invariants ----------

    public function testRejectsB2bWithCarrier(): void
    {
        // 藍新規格：B2B 發票不可使用載具（B2B 走統編寄送）
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('B2B 發票不可使用載具');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'category' => Category::B2B,
            'buyerUbn' => '12345678',
            'carrierType' => CarrierType::Mobile,
            'carrierNum' => '/ABC1234',
        ]);
    }

    public function testRejectsB2bWithLoveCode(): void
    {
        // 藍新規格：B2B 發票不可使用捐贈碼
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('B2B 發票不可使用 loveCode');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'category' => Category::B2B,
            'buyerUbn' => '12345678',
            'loveCode' => '13994',
        ]);
    }

    public function testRejectsCarrierAndLoveCodeTogether(): void
    {
        // 藍新規格：載具與捐贈碼互斥
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('互斥');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::Mobile,
            'carrierNum' => '/ABC1234',
            'loveCode' => '13994',
        ]);
    }

    public function testRejectsCarrierTypeWithoutCarrierNum(): void
    {
        // pair 必須一致：carrierType 設了 carrierNum 必須非空（取代過去 silent treat as missing 行為）
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('carrierType 與 carrierNum');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            'carrierType' => CarrierType::Mobile,
            // 沒帶 carrierNum
            'loveCode' => '13994', // 補滿 cross-field，否則會打到 missing carrier-or-lovecode
        ]);
    }

    public function testRejectsCarrierNumWithoutCarrierType(): void
    {
        // pair 必須一致：carrierNum 設了 carrierType 必須非 null
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('carrierType 與 carrierNum');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::No,
            // 沒帶 carrierType
            'carrierNum' => '/ABC1234',
            'loveCode' => '13994',
        ]);
    }

    public function testB2cZeroRatePrintFlagNoStillRequiresCarrierOrLoveCode(): void
    {
        // 0.5.0 起 B2C+PrintFlag=N 的攔截不再限定 TaxType=Taxable
        // （取代過去 testB2cZeroRatePrintFlagNoIsAllowed 的放行行為）
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('B2C + printFlag=N');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'taxType' => TaxType::ZeroRate,
            'amount' => 500,
            'taxAmount' => 0,
            'totalAmount' => 500,
            'printFlag' => PrintFlag::No,
        ]);
    }

    public function testB2cMixedTaxPrintFlagNoStillRequiresCarrierOrLoveCode(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('B2C + printFlag=N');

        new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'taxType' => TaxType::Mixed,
            'printFlag' => PrintFlag::No,
            'items' => [
                new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500, taxType: '1'),
            ],
        ]);
    }

    public function testAcceptsB2cPrintYesWithoutCarrierOrLoveCode(): void
    {
        // 正例：B2C + PrintFlag=Y（紙本）就不需要 carrier/loveCode
        $request = new InvoiceIssueRequest(...[
            ...$this->validBaseArgs(),
            'printFlag' => PrintFlag::Yes,
        ]);

        $this->assertSame(PrintFlag::Yes, $request->printFlag);
    }
}
