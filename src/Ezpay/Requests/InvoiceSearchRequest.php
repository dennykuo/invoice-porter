<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests;

use InvoicePorter\Ezpay\Enums\DisplayFlag;
use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Responses\InvoiceSearchResponse;

final class InvoiceSearchRequest extends EzpayRequest
{
    public function __construct(
        public readonly SearchType $searchType,
        public readonly string $merchantOrderNo,
        public readonly ?string $invoiceNumber = null,
        public readonly ?string $randomNum = null,
        public readonly int|float|null $totalAmount = null,
        public readonly ?DisplayFlag $displayFlag = null,
    ) {
        if ($merchantOrderNo === '') {
            throw new EzpayValidationException('merchantOrderNo 不可為空');
        }
        if ($searchType === SearchType::ByInvoiceNumber) {
            if ($invoiceNumber === null || $invoiceNumber === '') {
                throw new EzpayValidationException('SearchType=0 (InvoiceNumber) 必須提供 invoiceNumber');
            }
            if ($randomNum === null || $randomNum === '') {
                throw new EzpayValidationException('SearchType=0 (InvoiceNumber) 必須提供 randomNum');
            }
        }
    }

    public function uri(): string
    {
        return 'invoice_search';
    }

    public function version(): string
    {
        return '1.3';
    }

    public function responseClass(): string
    {
        return InvoiceSearchResponse::class;
    }

    public function checkCodeFields(): ?array
    {
        if ($this->displayFlag === DisplayFlag::Redirect) {
            return null;
        }

        return ['MerchantID', 'MerchantOrderNo', 'InvoiceTransNo', 'TotalAmt', 'RandomNum'];
    }

    public function toEncryptablePayload(): array
    {
        $payload = [
            'RespondType' => $this->respondType()->value,
            'Version' => $this->version(),
            'TimeStamp' => (string) time(),
            'SearchType' => $this->searchType->value,
            'MerchantOrderNo' => $this->merchantOrderNo,
        ];

        if ($this->invoiceNumber !== null) {
            $payload['InvoiceNumber'] = $this->invoiceNumber;
        }
        if ($this->randomNum !== null) {
            $payload['RandomNum'] = $this->randomNum;
        }
        if ($this->totalAmount !== null) {
            $payload['TotalAmt'] = (string) $this->totalAmount;
        }
        if ($this->displayFlag !== null) {
            $payload['DisplayFlag'] = $this->displayFlag->value;
        }

        return $payload;
    }
}
