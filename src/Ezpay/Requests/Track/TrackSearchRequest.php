<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests\Track;

use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\EzpayRequest;
use InvoicePorter\Ezpay\Responses\Track\TrackSearchResponse;

/**
 * 字軌資料查詢（EZP_Track_1.0.0 §5-3）：依年份、期別、狀態旗標或字軌編號條件查詢。
 *
 * 文件僅要求 year + term；其餘條件選填。預設不驗 CheckCode，可透過 `expectCheckCode: true` 開啟。
 */
final class TrackSearchRequest extends EzpayRequest
{
    public function __construct(
        public readonly ?string $year = null,
        public readonly ?InvoiceTerm $term = null,
        public readonly ?TrackFlag $flag = null,
        public readonly ?string $managementNo = null,
        public readonly bool $expectCheckCode = false,
    ) {
        if ($year !== null && (strlen($year) !== 3 || !ctype_digit($year))) {
            throw new EzpayValidationException('year 必須為 3 位數字（民國年三碼）');
        }
    }

    public function uri(): string
    {
        return 'Api_number_management/searchNumber';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function responseClass(): string
    {
        return TrackSearchResponse::class;
    }

    public function checkCodeFields(): ?array
    {
        return $this->expectCheckCode
            ? ['CompanyId', 'AphabeticLetter', 'StartNumber', 'EndNumber', 'ManagementNo']
            : null;
    }

    public function toEncryptablePayload(): array
    {
        return [
            'RespondType' => $this->respondType()->value,
            'Version' => $this->version(),
            'TimeStamp' => (string) time(),
            'Year' => $this->year ?? '',
            'Term' => $this->term !== null ? $this->term->value : '',
            'Flag' => $this->flag !== null ? $this->flag->value : '',
            'ManagementNo' => $this->managementNo ?? '',
        ];
    }
}
