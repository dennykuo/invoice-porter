<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests;

use InvoicePorter\Ezpay\Enums\AllowanceTouchStatus;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Responses\AllowanceTouchIssueResponse;

final class AllowanceTouchIssueRequest extends EzpayRequest
{
    public function __construct(
        public readonly string $allowanceNo,
        public readonly AllowanceTouchStatus $status,
        public readonly bool $expectCheckCode = false,
    ) {
        if ($allowanceNo === '') {
            throw new EzpayValidationException('allowanceNo 不可為空');
        }
    }

    public function uri(): string
    {
        return 'allowance_touch_issue';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function responseClass(): string
    {
        return AllowanceTouchIssueResponse::class;
    }

    public function checkCodeFields(): ?array
    {
        return $this->expectCheckCode
            ? ['MerchantID', 'MerchantOrderNo', 'InvoiceTransNo', 'TotalAmt', 'RandomNum']
            : null;
    }

    public function toEncryptablePayload(): array
    {
        return [
            'RespondType' => $this->respondType()->value,
            'Version' => $this->version(),
            'TimeStamp' => (string) time(),
            'AllowanceNo' => $this->allowanceNo,
            'Status' => $this->status->value,
        ];
    }
}
