<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Validation;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;

/**
 * 統一驗證買方 email 欄位。
 *
 * 規格依藍新 EZP_INVI_1.2.2：Varchar(80)、需符合 email 格式。
 * 過去 `InvoiceIssueRequest` 有長度檢查、`AllowanceIssueRequest` 漏了——抽到此處避免漂移。
 *
 * email 為 ASCII，因此採 strlen 計算 byte 數即可（一字一 byte）。
 */
final class BuyerEmailValidator
{
    public const MAX_LENGTH = 80;

    /**
     * 接受 null / 空字串（視為未提供）；提供時必須符合格式與長度。
     *
     * @throws EzpayValidationException 格式錯誤或超過 80 字。
     */
    public static function assert(?string $email): void
    {
        if ($email === null || $email === '') {
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new EzpayValidationException('buyerEmail 格式錯誤');
        }
        if (strlen($email) > self::MAX_LENGTH) {
            throw new EzpayValidationException(sprintf(
                'buyerEmail 長度不可超過 %d',
                self::MAX_LENGTH,
            ));
        }
    }
}
