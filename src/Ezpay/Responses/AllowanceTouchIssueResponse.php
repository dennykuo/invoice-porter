<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

final class AllowanceTouchIssueResponse extends EzpayResponse
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

    public function totalAmount(): ?string
    {
        return $this->string('AllowanceAmt');
    }

    public function createTime(): ?string
    {
        return $this->string('CreateTime');
    }
}
