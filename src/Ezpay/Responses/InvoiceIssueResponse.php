<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

final class InvoiceIssueResponse extends EzpayResponse
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

    public function invoiceTransNoFull(): ?string
    {
        return $this->string('InvoiceTransNo');
    }

    public function randomNum(): ?string
    {
        return $this->string('RandomNum');
    }

    public function createTime(): ?string
    {
        return $this->string('CreateTime');
    }

    public function checkCode(): ?string
    {
        return $this->string('CheckCode');
    }

    public function barcode(): ?string
    {
        return $this->string('BarCode');
    }

    public function qrcodeL(): ?string
    {
        return $this->string('QRcodeL');
    }

    public function qrcodeR(): ?string
    {
        return $this->string('QRcodeR');
    }
}
