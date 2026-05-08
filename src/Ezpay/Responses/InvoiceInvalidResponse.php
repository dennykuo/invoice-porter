<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

final class InvoiceInvalidResponse extends EzpayResponse
{
    public function merchantId(): string
    {
        return $this->requireString('MerchantID');
    }

    public function invoiceNumber(): ?string
    {
        return $this->string('InvoiceNumber');
    }

    public function createTime(): ?string
    {
        return $this->string('CreateTime');
    }
}
