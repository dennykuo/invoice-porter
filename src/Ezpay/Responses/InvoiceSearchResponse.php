<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

use InvoicePorter\Ezpay\Enums\InvoiceLifecycleStatus;
use InvoicePorter\Ezpay\Enums\UploadStatus;

final class InvoiceSearchResponse extends EzpayResponse
{
    public function merchantId(): string
    {
        return $this->requireString('MerchantID');
    }

    public function invoiceTransNo(): string
    {
        return $this->requireString('InvoiceTransNo');
    }

    public function merchantOrderNo(): string
    {
        return $this->requireString('MerchantOrderNo');
    }

    public function invoiceNumber(): ?string
    {
        return $this->string('InvoiceNumber');
    }

    public function totalAmount(): ?string
    {
        return $this->string('TotalAmt');
    }

    public function buyerName(): ?string
    {
        return $this->string('BuyerName');
    }

    public function buyerUbn(): ?string
    {
        return $this->string('BuyerUBN');
    }

    public function buyerEmail(): ?string
    {
        return $this->string('BuyerEmail');
    }

    public function createTime(): ?string
    {
        return $this->string('CreateTime');
    }

    public function createTimeAt(): ?\DateTimeImmutable
    {
        return $this->parseDateTime($this->createTime());
    }

    public function randomNum(): ?string
    {
        return $this->string('RandomNum');
    }

    public function checkCode(): ?string
    {
        return $this->string('CheckCode');
    }

    public function lifecycleStatus(): ?InvoiceLifecycleStatus
    {
        $value = $this->string('InvoiceStatus');
        return $value !== null ? InvoiceLifecycleStatus::tryFrom($value) : null;
    }

    public function uploadStatus(): ?UploadStatus
    {
        $value = $this->string('UploadStatus');
        return $value !== null ? UploadStatus::tryFrom($value) : null;
    }

    public function searchResultUrl(): ?string
    {
        return $this->string('InvoiceURL') ?? $this->string('SearchResultURL');
    }
}
