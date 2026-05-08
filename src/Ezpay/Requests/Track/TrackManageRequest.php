<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests\Track;

use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\EzpayRequest;
use InvoicePorter\Ezpay\Responses\Track\TrackManageResponse;

/**
 * 字軌資料管理（EZP_Track_1.0.0 §5-2）：變更某筆字軌的狀態旗標（暫停 / 正常 / 停止）。
 *
 * 預設不驗 CheckCode（仿折讓系列保守策略）；可透過 `expectCheckCode: true` 開啟。
 */
final class TrackManageRequest extends EzpayRequest
{
    public function __construct(
        public readonly string $managementNo,
        public readonly TrackFlag $flag,
        public readonly bool $expectCheckCode = false,
    ) {
        if ($managementNo === '') {
            throw new EzpayValidationException('managementNo 不可為空');
        }
    }

    public function uri(): string
    {
        return 'Api_number_management/manageNumber';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function responseClass(): string
    {
        return TrackManageResponse::class;
    }

    public function checkCodeFields(): ?array
    {
        return $this->expectCheckCode
            ? ['CompanyId', 'AphabeticLetter', 'StartNumber', 'EndNumber', 'ManagementNo']
            : null;
    }

    public function checkCodeHint(): array
    {
        return ['ManagementNo' => $this->managementNo];
    }

    public function toEncryptablePayload(): array
    {
        return [
            'RespondType' => $this->respondType()->value,
            'Version' => $this->version(),
            'TimeStamp' => (string) time(),
            'ManagementNo' => $this->managementNo,
            'Flag' => $this->flag->value,
        ];
    }
}
