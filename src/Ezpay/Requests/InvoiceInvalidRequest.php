<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Responses\InvoiceInvalidResponse;
use InvoicePorter\Ezpay\Validation\InvalidReasonValidator;

/**
 * 作廢發票。藍新對此 API 的回應未提供 CheckCode 計算所需 5 欄完整資訊；
 * 因此預設 **不驗** CheckCode，除非使用者建構時帶入 randomNum / invoiceTransNo / merchantOrderNo / totalAmount。
 */
final class InvoiceInvalidRequest extends EzpayRequest
{
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly string $invalidReason,
        public readonly ?string $randomNum = null,
        public readonly ?string $invoiceTransNo = null,
        public readonly ?string $merchantOrderNo = null,
        public readonly int|float|null $totalAmount = null,
    ) {
        if ($invoiceNumber === '') {
            throw new EzpayValidationException('invoiceNumber 不可為空');
        }
        InvalidReasonValidator::assert($invalidReason);
    }

    public function uri(): string
    {
        return 'invoice_invalid';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function responseClass(): string
    {
        return InvoiceInvalidResponse::class;
    }

    public function checkCodeFields(): ?array
    {
        if (
            $this->randomNum === null
            || $this->invoiceTransNo === null
            || $this->merchantOrderNo === null
            || $this->totalAmount === null
        ) {
            return null;
        }

        return ['MerchantID', 'MerchantOrderNo', 'InvoiceTransNo', 'TotalAmt', 'RandomNum'];
    }

    public function checkCodeHint(): array
    {
        $hint = [];
        if ($this->randomNum !== null) {
            $hint['RandomNum'] = $this->randomNum;
        }
        if ($this->invoiceTransNo !== null) {
            $hint['InvoiceTransNo'] = $this->invoiceTransNo;
        }
        if ($this->merchantOrderNo !== null) {
            $hint['MerchantOrderNo'] = $this->merchantOrderNo;
        }
        if ($this->totalAmount !== null) {
            $hint['TotalAmt'] = (string) $this->totalAmount;
        }

        return $hint;
    }

    public function toEncryptablePayload(): array
    {
        return [
            'RespondType' => $this->respondType()->value,
            'Version' => $this->version(),
            'TimeStamp' => (string) time(),
            'InvoiceNumber' => $this->invoiceNumber,
            'InvalidReason' => $this->invalidReason,
        ];
    }
}
