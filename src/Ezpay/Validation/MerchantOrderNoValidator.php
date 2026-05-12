<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Validation;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;

/**
 * 統一驗證 MerchantOrderNo 欄位。
 *
 * 規格依藍新 EZP_INVI_1.2.2 第 5-1 章節：Varchar(20)，僅允許英文、數字、底線。
 * 過去四個 Request（InvoiceIssue / InvoiceTouchIssue / InvoiceSearch / AllowanceIssue）
 * 各驗各的（且都漏），抽到此處統一管理避免漂移。
 */
final class MerchantOrderNoValidator
{
    public const MAX_LENGTH = 20;

    /**
     * 藍新規格：僅允許英、數字、底線。
     *
     * 公開以利呼叫端做 UI 端前置檢查時可參照同一規則（與後端一致避免漂移）。
     * 不會 auto-trim 前後空白，呼叫端若有此需要請於 assert() 前自行處理。
     */
    public const PATTERN = '/^[A-Za-z0-9_]+$/';

    /**
     * @throws EzpayValidationException 為空、超長、含非法字元三種情況之一。
     */
    public static function assert(string $value): void
    {
        if ($value === '') {
            throw new EzpayValidationException('merchantOrderNo 不可為空');
        }
        if (strlen($value) > self::MAX_LENGTH) {
            throw new EzpayValidationException(sprintf(
                'merchantOrderNo 長度不可超過 %d，目前 %d 字元',
                self::MAX_LENGTH,
                strlen($value),
            ));
        }
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new EzpayValidationException(sprintf(
                'merchantOrderNo 僅可包含英文、數字、底線（違規字元：%s）',
                self::summarizeInvalidChars($value),
            ));
        }
    }

    /**
     * 列出所有 unique 違規字元供 error message 點名問題；空白以 <space>、tab 以 <tab> 可視化。
     */
    private static function summarizeInvalidChars(string $value): string
    {
        preg_match_all('/[^A-Za-z0-9_]/u', $value, $matches);
        $unique = array_unique($matches[0]);

        return implode(',', array_map(
            static fn (string $c) => match ($c) {
                ' ' => '<space>',
                "\t" => '<tab>',
                "\n" => '<lf>',
                "\r" => '<cr>',
                default => $c,
            },
            $unique,
        ));
    }
}
