<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests;

use InvoicePorter\Ezpay\Enums\CarrierType;
use InvoicePorter\Ezpay\Enums\Category;
use InvoicePorter\Ezpay\Enums\CustomsClearance;
use InvoicePorter\Ezpay\Enums\InvoiceStatus;
use InvoicePorter\Ezpay\Enums\KioskPrintFlag;
use InvoicePorter\Ezpay\Enums\PrintFlag;
use InvoicePorter\Ezpay\Enums\TaxType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\Items\InvoiceItem;
use InvoicePorter\Ezpay\Responses\InvoiceIssueResponse;
use InvoicePorter\Ezpay\Validation\BuyerEmailValidator;
use InvoicePorter\Ezpay\Validation\MerchantOrderNoValidator;

final class InvoiceIssueRequest extends EzpayRequest
{
    // 藍新 EZP_INVI_1.2.2 欄位規格（見手冊 5-1）。集中宣告避免 hardcode 散布。
    public const BUYER_NAME_MAX_LENGTH = 60;
    public const BUYER_ADDRESS_MAX_LENGTH = 100;
    /** @deprecated 0.5.1 起改用 BuyerEmailValidator::MAX_LENGTH，此 alias 將於 0.6.0 移除 */
    public const BUYER_EMAIL_MAX_LENGTH = BuyerEmailValidator::MAX_LENGTH;
    public const COMMENT_MAX_LENGTH = 200;
    public const CARRIER_NUM_MEMBER_MAX_LENGTH = 64; // 會員載具藍新無明確規範，設保險上限

    private const BUYER_UBN_PATTERN = '/^\d{8}$/';
    private const LOVE_CODE_PATTERN = '/^\d{3,7}$/';
    private const CARRIER_NUM_MOBILE_PATTERN = '/^\/[A-Z0-9.\-+]{7}$/';
    private const CARRIER_NUM_CDC_PATTERN = '/^[A-Z]{2}\d{14}$/';

    /**
     * @param list<InvoiceItem> $items
     */
    public function __construct(
        public readonly InvoiceStatus $status,
        public readonly string $merchantOrderNo,
        public readonly Category $category,
        public readonly TaxType $taxType,
        public readonly int|float $amount,
        public readonly int|float $taxAmount,
        public readonly int|float $totalAmount,
        public readonly array $items,
        // PrintFlag 自 0.5.0 起為必填：是否寄送紙本是業務語意決策，過去預設 PrintFlag::No
        // 配合 B2C 又強制要求載具/捐贈碼，等於是讓「最少參數呼叫」的使用者直接掉坑。
        public readonly PrintFlag $printFlag,
        public readonly ?KioskPrintFlag $kioskPrintFlag = null,
        public readonly ?string $createStatusTime = null,
        public readonly ?string $buyerName = null,
        public readonly ?string $buyerUbn = null,
        public readonly ?string $buyerAddress = null,
        public readonly ?string $buyerEmail = null,
        public readonly int|float $taxRate = 5,
        public readonly ?CarrierType $carrierType = null,
        public readonly ?string $carrierNum = null,
        public readonly ?string $loveCode = null,
        public readonly ?CustomsClearance $customsClearance = null,
        public readonly ?string $comment = null,
    ) {
        MerchantOrderNoValidator::assert($merchantOrderNo);
        if (count($items) === 0) {
            throw new EzpayValidationException('items 不可為空');
        }
        foreach ($items as $item) {
            if (!$item instanceof InvoiceItem) {
                throw new EzpayValidationException('items 必須為 InvoiceItem 集合');
            }
        }
        if ($amount < 0) {
            throw new EzpayValidationException('amount 不可為負');
        }
        if ($taxAmount < 0) {
            throw new EzpayValidationException('taxAmount 不可為負');
        }
        if ($totalAmount <= 0) {
            throw new EzpayValidationException('totalAmount 必須大於 0');
        }
        if ($status === InvoiceStatus::Scheduled && ($createStatusTime === null || $createStatusTime === '')) {
            throw new EzpayValidationException('Status=3 (延遲開立) 必須提供 createStatusTime');
        }
        self::assertBuyerFields($buyerName, $buyerUbn, $buyerAddress, $buyerEmail);
        self::assertLoveCode($loveCode);
        self::assertCarrierNum($carrierType, $carrierNum);
        self::assertComment($comment);
        self::assertCrossField($category, $printFlag, $carrierType, $carrierNum, $loveCode, $buyerUbn);
    }

    /**
     * 跨欄位 invariants（藍新規格中「擇一」「互斥」「依其他欄位決定必填」的硬約束）。
     *
     * 規則：
     * - B2B 必須提供 buyerUbn
     * - B2B 不可使用載具
     * - B2B 不可使用捐贈碼
     * - 載具與捐贈碼互斥（不可同時提供）
     * - carrierType / carrierNum 必須同時提供或同時省略
     * - B2C + printFlag=N 必須提供載具或捐贈碼擇一
     *
     * @see CHANGELOG.md 0.5.0 — 此 method 的 BC change 細節
     */
    private static function assertCrossField(
        Category $category,
        PrintFlag $printFlag,
        ?CarrierType $carrierType,
        ?string $carrierNum,
        ?string $loveCode,
        ?string $buyerUbn,
    ): void {
        $carrierTypeSet = $carrierType !== null;
        $carrierNumSet = $carrierNum !== null && $carrierNum !== '';
        if ($carrierTypeSet !== $carrierNumSet) {
            throw new EzpayValidationException(
                'carrierType 與 carrierNum 必須同時提供或同時省略',
            );
        }

        $hasCarrier = $carrierTypeSet && $carrierNumSet;
        $hasLoveCode = $loveCode !== null && $loveCode !== '';

        if ($hasCarrier && $hasLoveCode) {
            throw new EzpayValidationException(
                '載具（carrierType + carrierNum）與 loveCode 互斥，不可同時提供',
            );
        }

        if ($category === Category::B2B) {
            if ($buyerUbn === null || $buyerUbn === '') {
                throw new EzpayValidationException('B2B 發票必須提供 buyerUbn');
            }
            if ($hasCarrier) {
                throw new EzpayValidationException('B2B 發票不可使用載具（carrierType / carrierNum）');
            }
            if ($hasLoveCode) {
                throw new EzpayValidationException('B2B 發票不可使用 loveCode');
            }
        }

        if (
            $category === Category::B2C
            && $printFlag === PrintFlag::No
            && !$hasCarrier
            && !$hasLoveCode
        ) {
            throw new EzpayValidationException(
                'B2C + printFlag=N 必須提供載具（carrierType + carrierNum）或 loveCode 擇一',
            );
        }
    }

    private static function assertBuyerFields(
        ?string $buyerName,
        ?string $buyerUbn,
        ?string $buyerAddress,
        ?string $buyerEmail,
    ): void {
        if ($buyerName !== null && $buyerName !== '' && mb_strlen($buyerName) > self::BUYER_NAME_MAX_LENGTH) {
            throw new EzpayValidationException(sprintf(
                'buyerName 長度不可超過 %d 字',
                self::BUYER_NAME_MAX_LENGTH,
            ));
        }
        if (
            $buyerAddress !== null
            && $buyerAddress !== ''
            && mb_strlen($buyerAddress) > self::BUYER_ADDRESS_MAX_LENGTH
        ) {
            throw new EzpayValidationException(sprintf(
                'buyerAddress 長度不可超過 %d 字',
                self::BUYER_ADDRESS_MAX_LENGTH,
            ));
        }
        if ($buyerUbn !== null && $buyerUbn !== '' && preg_match(self::BUYER_UBN_PATTERN, $buyerUbn) !== 1) {
            throw new EzpayValidationException('buyerUbn 必須為 8 碼純數字');
        }
        BuyerEmailValidator::assert($buyerEmail);
    }

    private static function assertLoveCode(?string $loveCode): void
    {
        if ($loveCode === null || $loveCode === '') {
            return;
        }
        if (preg_match(self::LOVE_CODE_PATTERN, $loveCode) !== 1) {
            throw new EzpayValidationException('loveCode 必須為 3 至 7 碼純數字（愛心碼）');
        }
    }

    private static function assertCarrierNum(?CarrierType $type, ?string $num): void
    {
        if ($type === null || $num === null || $num === '') {
            return;
        }
        $valid = match ($type) {
            CarrierType::Mobile => preg_match(self::CARRIER_NUM_MOBILE_PATTERN, $num) === 1,
            CarrierType::CitizenDigitalCertificate => preg_match(self::CARRIER_NUM_CDC_PATTERN, $num) === 1,
            // 會員載具藍新無固定格式（廠商自訂），僅擋過長
            CarrierType::Member => strlen($num) <= self::CARRIER_NUM_MEMBER_MAX_LENGTH,
        };
        if (!$valid) {
            throw new EzpayValidationException(self::carrierNumErrorMessage($type));
        }
    }

    private static function carrierNumErrorMessage(CarrierType $type): string
    {
        return match ($type) {
            CarrierType::Mobile => 'carrierNum 手機條碼格式錯誤（須以 / 開頭、後接 7 碼大寫英數或 .-+）',
            CarrierType::CitizenDigitalCertificate => 'carrierNum 自然人憑證格式錯誤（須為 2 大寫英文 + 14 數字）',
            CarrierType::Member => sprintf(
                'carrierNum 會員載具長度不可超過 %d',
                self::CARRIER_NUM_MEMBER_MAX_LENGTH,
            ),
        };
    }

    private static function assertComment(?string $comment): void
    {
        if ($comment !== null && $comment !== '' && mb_strlen($comment) > self::COMMENT_MAX_LENGTH) {
            throw new EzpayValidationException(sprintf(
                'comment 長度不可超過 %d 字',
                self::COMMENT_MAX_LENGTH,
            ));
        }
    }

    public function uri(): string
    {
        return 'invoice_issue';
    }

    public function version(): string
    {
        return '1.5';
    }

    public function responseClass(): string
    {
        return InvoiceIssueResponse::class;
    }

    public function checkCodeFields(): array
    {
        return ['MerchantID', 'MerchantOrderNo', 'InvoiceTransNo', 'TotalAmt', 'RandomNum'];
    }

    public function toEncryptablePayload(): array
    {
        $payload = [
            'RespondType' => $this->respondType()->value,
            'Version' => $this->version(),
            'TimeStamp' => (string) time(),
            'TransNum' => '',
            'MerchantOrderNo' => $this->merchantOrderNo,
            'Status' => $this->status->value,
            'Category' => $this->category->value,
            'BuyerName' => $this->buyerName ?? '',
            'BuyerUBN' => $this->buyerUbn ?? '',
            'BuyerAddress' => $this->buyerAddress ?? '',
            'BuyerEmail' => $this->buyerEmail ?? '',
            'CarrierType' => $this->carrierType !== null ? $this->carrierType->value : '',
            'CarrierNum' => $this->carrierNum ?? '',
            'LoveCode' => $this->loveCode ?? '',
            'PrintFlag' => $this->printFlag->value,
            'KioskPrintFlag' => $this->kioskPrintFlag !== null ? $this->kioskPrintFlag->value : '',
            'TaxType' => $this->taxType->value,
            'TaxRate' => (string) $this->taxRate,
            'CustomsClearance' => $this->customsClearance !== null ? $this->customsClearance->value : '',
            'Amt' => (string) $this->amount,
            'AmtSales' => '',
            'AmtZero' => '',
            'AmtFree' => '',
            'TaxAmt' => (string) $this->taxAmount,
            'TotalAmt' => (string) $this->totalAmount,
            'ItemName' => implode('|', array_map(static fn (InvoiceItem $i) => $i->name, $this->items)),
            'ItemCount' => implode('|', array_map(static fn (InvoiceItem $i) => (string) $i->count, $this->items)),
            'ItemUnit' => implode('|', array_map(static fn (InvoiceItem $i) => $i->unit, $this->items)),
            'ItemPrice' => implode('|', array_map(static fn (InvoiceItem $i) => (string) $i->price, $this->items)),
            'ItemAmt' => implode('|', array_map(static fn (InvoiceItem $i) => (string) $i->amount, $this->items)),
            'Comment' => $this->comment ?? '',
        ];

        if ($this->status === InvoiceStatus::Scheduled) {
            $payload['CreateStatusTime'] = (string) $this->createStatusTime;
        }

        if ($this->taxType === TaxType::Mixed) {
            $payload['ItemTaxType'] = implode('|', array_map(
                static fn (InvoiceItem $i) => $i->taxType ?? '',
                $this->items,
            ));
        }

        return $payload;
    }
}
