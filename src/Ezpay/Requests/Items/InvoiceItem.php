<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests\Items;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;

/**
 * 開立發票單項商品。最終會以「|」串成 ItemName / ItemCount / ItemUnit / ItemPrice / ItemAmt 字串送出。
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
        if ($name === '') {
            throw new EzpayValidationException('InvoiceItem.name 不可為空');
        }
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
