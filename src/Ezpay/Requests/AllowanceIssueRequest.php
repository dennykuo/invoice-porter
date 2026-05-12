<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests;

use InvoicePorter\Ezpay\Enums\AllowanceConfirmStatus;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\Items\AllowanceItem;
use InvoicePorter\Ezpay\Responses\AllowanceIssueResponse;
use InvoicePorter\Ezpay\Validation\BuyerEmailValidator;
use InvoicePorter\Ezpay\Validation\MerchantOrderNoValidator;

final class AllowanceIssueRequest extends EzpayRequest
{
    /**
     * @param list<AllowanceItem> $items
     */
    public function __construct(
        public readonly string $invoiceNo,
        public readonly string $merchantOrderNo,
        public readonly int|float $totalAmount,
        public readonly int|float $taxAmount,
        public readonly array $items,
        public readonly AllowanceConfirmStatus $confirmStatus = AllowanceConfirmStatus::Immediate,
        public readonly ?string $buyerEmail = null,
        public readonly ?string $buyerName = null,
        public readonly bool $expectCheckCode = false,
    ) {
        if ($invoiceNo === '') {
            throw new EzpayValidationException('invoiceNo 不可為空');
        }
        MerchantOrderNoValidator::assert($merchantOrderNo);
        if ($totalAmount <= 0) {
            throw new EzpayValidationException('totalAmount 必須大於 0');
        }
        if ($taxAmount < 0) {
            throw new EzpayValidationException('taxAmount 不可為負');
        }
        if (count($items) === 0) {
            throw new EzpayValidationException('items 不可為空');
        }
        foreach ($items as $item) {
            if (!$item instanceof AllowanceItem) {
                throw new EzpayValidationException('items 必須為 AllowanceItem 集合');
            }
        }
        BuyerEmailValidator::assert($buyerEmail);
    }

    public function uri(): string
    {
        return 'allowance_issue';
    }

    public function version(): string
    {
        return '1.3';
    }

    public function responseClass(): string
    {
        return AllowanceIssueResponse::class;
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
            'InvoiceNo' => $this->invoiceNo,
            'MerchantOrderNo' => $this->merchantOrderNo,
            'ItemName' => implode('|', array_map(static fn (AllowanceItem $i) => $i->name, $this->items)),
            'ItemCount' => implode('|', array_map(static fn (AllowanceItem $i) => (string) $i->count, $this->items)),
            'ItemUnit' => implode('|', array_map(static fn (AllowanceItem $i) => $i->unit, $this->items)),
            'ItemPrice' => implode('|', array_map(static fn (AllowanceItem $i) => (string) $i->price, $this->items)),
            'ItemAmt' => implode('|', array_map(static fn (AllowanceItem $i) => (string) $i->amount, $this->items)),
            'ItemTaxAmt' => implode('|', array_map(static fn (AllowanceItem $i) => (string) $i->taxAmount, $this->items)),
            'TotalAmt' => (string) $this->totalAmount,
            'TaxAmt' => (string) $this->taxAmount,
            'BuyerName' => $this->buyerName ?? '',
            'BuyerEmail' => $this->buyerEmail ?? '',
            'Status' => $this->confirmStatus->value,
        ];
    }
}
