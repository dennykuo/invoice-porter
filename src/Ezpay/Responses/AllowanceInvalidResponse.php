<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

final class AllowanceInvalidResponse extends EzpayResponse
{
    public function merchantId(): string
    {
        return $this->requireString('MerchantID');
    }

    public function allowanceNo(): ?string
    {
        return $this->string('AllowanceNo');
    }

    public function invalidTime(): ?string
    {
        return $this->string('CreateTime');
    }

    public function invalidTimeAt(): ?\DateTimeImmutable
    {
        return $this->parseDateTime($this->invalidTime());
    }
}
