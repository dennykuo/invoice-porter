<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Responses\AllowanceInvalidResponse;

final class AllowanceInvalidRequest extends EzpayRequest
{
    public function __construct(
        public readonly string $allowanceNo,
        public readonly string $invalidReason,
        public readonly bool $expectCheckCode = false,
    ) {
        if ($allowanceNo === '') {
            throw new EzpayValidationException('allowanceNo 不可為空');
        }
        if ($invalidReason === '') {
            throw new EzpayValidationException('invalidReason 不可為空');
        }
        if (mb_strlen($invalidReason) > 20) {
            throw new EzpayValidationException('invalidReason 長度不可超過 20');
        }
    }

    public function uri(): string
    {
        // 文件附錄此端點為駝峰：allowanceInvalid
        return 'allowanceInvalid';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function responseClass(): string
    {
        return AllowanceInvalidResponse::class;
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
            'InvalidReason' => $this->invalidReason,
        ];
    }
}
