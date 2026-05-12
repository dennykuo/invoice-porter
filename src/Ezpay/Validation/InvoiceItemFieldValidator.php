<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Validation;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;

/**
 * InvoiceItem / AllowanceItem 兩種品項共用的欄位驗證。
 *
 * 多個 item 會以 "|" 串成 ItemName / ItemCount / ItemUnit / ItemPrice / ItemAmt 字串送出
 * （見 InvoiceIssueRequest / AllowanceIssueRequest 的 toEncryptablePayload()）。
 * 因此 name / unit 內含 "|" 會讓藍新把單一品項解析成多項，造成 silent data corruption。
 *
 * 同時藍新規格 ItemName Varchar(30)，超長會被遠端拒絕。本層先擋下避免 round-trip。
 *
 * 對外暴露的 method 對應「具體欄位」（與 MerchantOrderNoValidator::assert() 同風格），
 * 呼叫端不必傳 magic string label，避免 Item 重命名時 message 漂移。
 */
final class InvoiceItemFieldValidator
{
    public const NAME_MAX_LENGTH = 30;

    public static function assertInvoiceItemName(string $name): void
    {
        self::assertName('InvoiceItem.name', $name);
    }

    public static function assertInvoiceItemUnit(string $unit): void
    {
        self::assertNoPipe('InvoiceItem.unit', $unit);
    }

    public static function assertAllowanceItemName(string $name): void
    {
        self::assertName('AllowanceItem.name', $name);
    }

    public static function assertAllowanceItemUnit(string $unit): void
    {
        self::assertNoPipe('AllowanceItem.unit', $unit);
    }

    /** 中文發票品名以字元為單位（同 invalidReason 慣例）。 */
    private static function assertName(string $field, string $name): void
    {
        if ($name === '') {
            throw new EzpayValidationException(sprintf('%s 不可為空', $field));
        }
        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new EzpayValidationException(sprintf(
                '%s 長度不可超過 %d 字',
                $field,
                self::NAME_MAX_LENGTH,
            ));
        }
        self::assertNoPipe($field, $name);
    }

    private static function assertNoPipe(string $field, string $value): void
    {
        if (str_contains($value, '|')) {
            throw new EzpayValidationException(sprintf(
                '%s 不可包含「|」字元（藍新以 | 串接多品項，含此字元會造成資料毀損）',
                $field,
            ));
        }
    }
}
