<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Validation;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;

/**
 * 統一驗證作廢 / 折讓作廢的 `invalidReason` 欄位。
 *
 * 規格依藍新 EZP_INVI_1.2.2：Varchar(20)。中文以字元為單位（藍新後台「一字算一字」），
 * 因此採 mb_strlen 而非 strlen — 否則 7 個中文（21 byte）即會被誤擋。
 *
 * 過去 `InvoiceInvalidRequest` / `AllowanceInvalidRequest` 各寫一份且驗證順序不一致，
 * 抽到此處統一管理避免漂移。
 */
final class InvalidReasonValidator
{
    public const MAX_LENGTH = 20;

    /**
     * @throws EzpayValidationException 為空或超過 20 字時拋出。
     */
    public static function assert(string $value): void
    {
        if ($value === '') {
            throw new EzpayValidationException('invalidReason 不可為空');
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new EzpayValidationException(sprintf(
                'invalidReason 長度不可超過 %d 字',
                self::MAX_LENGTH,
            ));
        }
    }
}
