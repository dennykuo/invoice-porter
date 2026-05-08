<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses\Track;

use InvoicePorter\Ezpay\Enums\InvoiceType;
use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Responses\EzpayResponse;

/**
 * 字軌資料管理的回應（EZP_Track_1.0.0 §5-2）。
 *
 * 藍新管理回應與新增回應結構相同，因此欄位 mirror `TrackCreateResponse`。
 */
final class TrackManageResponse extends EzpayResponse
{
    public function managementNo(): ?string
    {
        return $this->string('ManagementNo');
    }

    public function aphabeticLetter(): ?string
    {
        return $this->string('AphabeticLetter');
    }

    public function startNumber(): ?string
    {
        return $this->string('StartNumber');
    }

    public function endNumber(): ?string
    {
        return $this->string('EndNumber');
    }

    public function type(): ?InvoiceType
    {
        $value = $this->string('Type');
        return $value !== null ? InvoiceType::tryFrom($value) : null;
    }

    public function lastNumber(): ?string
    {
        return $this->string('LastNumber');
    }

    public function flag(): ?TrackFlag
    {
        $value = $this->string('Flag');
        return $value !== null ? TrackFlag::tryFrom($value) : null;
    }

    public function createDatetime(): ?string
    {
        return $this->string('CreateDatetime');
    }

    public function checkCode(): ?string
    {
        return $this->string('CheckCode');
    }
}
