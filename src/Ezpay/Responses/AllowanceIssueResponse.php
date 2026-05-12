<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

final class AllowanceIssueResponse extends EzpayResponse
{
    public function merchantId(): string
    {
        return $this->requireString('MerchantID');
    }

    public function allowanceNo(): ?string
    {
        return $this->string('AllowanceNo');
    }

    public function invoiceNumber(): ?string
    {
        return $this->string('InvoiceNumber');
    }

    public function merchantOrderNo(): ?string
    {
        return $this->string('MerchantOrderNo');
    }

    public function totalAmount(): ?string
    {
        return $this->string('AllowanceAmt');
    }

    public function remainAmount(): ?string
    {
        return $this->string('RemainAmt');
    }

    public function createTime(): ?string
    {
        return $this->string('CreateTime');
    }

    public function createTimeAt(): ?\DateTimeImmutable
    {
        return $this->parseDateTime($this->createTime());
    }

    public function checkCode(): ?string
    {
        return $this->string('CheckCode');
    }
}
