<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Responses\InvoiceTouchIssueResponse;

final class InvoiceTouchIssueRequest extends EzpayRequest
{
    public function __construct(
        public readonly string $merchantOrderNo,
        public readonly int|float $totalAmount,
    ) {
        if ($merchantOrderNo === '') {
            throw new EzpayValidationException('merchantOrderNo 不可為空');
        }
        if ($totalAmount <= 0) {
            throw new EzpayValidationException('totalAmount 必須大於 0');
        }
    }

    public function uri(): string
    {
        return 'invoice_touch_issue';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function responseClass(): string
    {
        return InvoiceTouchIssueResponse::class;
    }

    public function checkCodeFields(): array
    {
        return ['MerchantID', 'MerchantOrderNo', 'InvoiceTransNo', 'TotalAmt', 'RandomNum'];
    }

    public function toEncryptablePayload(): array
    {
        return [
            'RespondType' => $this->respondType()->value,
            'Version' => $this->version(),
            'TimeStamp' => (string) time(),
            'MerchantOrderNo' => $this->merchantOrderNo,
            'TotalAmt' => (string) $this->totalAmount,
        ];
    }
}
