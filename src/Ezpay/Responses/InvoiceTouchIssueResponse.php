<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

final class InvoiceTouchIssueResponse extends EzpayResponse
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

    public function randomNum(): ?string
    {
        return $this->string('RandomNum');
    }

    public function checkCode(): ?string
    {
        return $this->string('CheckCode');
    }
}
