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

final class InvoiceIssueRequest extends EzpayRequest
{
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
        public readonly PrintFlag $printFlag = PrintFlag::No,
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
        if ($merchantOrderNo === '' || strlen($merchantOrderNo) > 30) {
            throw new EzpayValidationException('merchantOrderNo 必填，且長度不可超過 30');
        }
        if (count($items) === 0) {
            throw new EzpayValidationException('items 不可為空');
        }
        foreach ($items as $item) {
            if (!$item instanceof InvoiceItem) {
                throw new EzpayValidationException('items 必須為 InvoiceItem 集合');
            }
        }
        if ($amount < 0 || $taxAmount < 0 || $totalAmount <= 0) {
            throw new EzpayValidationException('Amt / TaxAmt 不可為負，TotalAmt 必須大於 0');
        }
        if ($status === InvoiceStatus::Scheduled && ($createStatusTime === null || $createStatusTime === '')) {
            throw new EzpayValidationException('Status=3 (延遲開立) 必須提供 createStatusTime');
        }
        if ($category === Category::B2B && ($buyerUbn === null || $buyerUbn === '')) {
            throw new EzpayValidationException('B2B 發票必須提供 buyerUbn');
        }
        if ($buyerEmail !== null && $buyerEmail !== '' && !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new EzpayValidationException('buyerEmail 格式錯誤');
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
