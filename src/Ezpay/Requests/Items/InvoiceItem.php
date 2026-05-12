<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests\Items;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Validation\InvoiceItemFieldValidator;

/**
 * 開立發票單項商品。最終會以「|」串成 ItemName / ItemCount / ItemUnit / ItemPrice / ItemAmt 字串送出。
 *
 * name 與 unit 內含「|」會造成 silent data corruption，由 InvoiceItemFieldValidator 統一擋下。
 */
final class InvoiceItem
{
    public function __construct(
        public readonly string $name,
        public readonly int|float $count,
        public readonly string $unit,
        public readonly int|float $price,
        public readonly int|float $amount,
        public readonly ?string $taxType = null,
    ) {
        InvoiceItemFieldValidator::assertInvoiceItemName($name);
        InvoiceItemFieldValidator::assertInvoiceItemUnit($unit);
        if ($count <= 0) {
            throw new EzpayValidationException('InvoiceItem.count 必須大於 0');
        }
        if ($price < 0) {
            throw new EzpayValidationException('InvoiceItem.price 不可為負');
        }
        if ($amount < 0) {
            throw new EzpayValidationException('InvoiceItem.amount 不可為負');
        }
    }
}
