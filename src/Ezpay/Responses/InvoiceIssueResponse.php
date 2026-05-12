<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

use InvoicePorter\Ezpay\Enums\DisplayFlag;
use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\InvoiceSearchRequest;

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

    public function createTimeAt(): ?\DateTimeImmutable
    {
        return $this->parseDateTime($this->createTime());
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

    /**
     * 從成功 issue 回應產生對應的查詢請求（`SearchType::ByInvoiceNumber` + `DisplayFlag::Redirect`）。
     *
     * 配 `$client->searchRedirectHtml($response->toSearchRequest())` 使用最 DRY。
     *
     * @throws EzpayValidationException 當 invoiceNumber 或 randomNum 為 null（藍新異常回應）
     */
    public function toSearchRequest(): InvoiceSearchRequest
    {
        $invoiceNumber = $this->invoiceNumber();
        $randomNum = $this->randomNum();
        $totalAmount = $this->totalAmount();

        if ($invoiceNumber === null || $randomNum === null) {
            throw new EzpayValidationException(
                'InvoiceIssueResponse 缺少 invoiceNumber 或 randomNum，無法產生查詢請求',
            );
        }

        return new InvoiceSearchRequest(
            searchType: SearchType::ByInvoiceNumber,
            merchantOrderNo: $this->merchantOrderNo(),
            invoiceNumber: $invoiceNumber,
            randomNum: $randomNum,
            totalAmount: $totalAmount !== null ? (float) $totalAmount : null,
            displayFlag: DisplayFlag::Redirect,
        );
    }
}
