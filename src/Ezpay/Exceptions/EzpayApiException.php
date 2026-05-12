<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Exceptions;

use Throwable;

final class EzpayApiException extends EzpayException
{
    /** 認定為「欄位格式錯誤」的 prefix（必填遺漏、長度超限、字元集違規），重試無意義。 */
    private const FIELD_FORMAT_ERROR_PREFIXES = ['INV100', 'INV700'];

    /** 認定為「憑證 / 簽章 / 解密錯誤」的 prefix（多半是 hashKey / hashIv 設定問題），重試無意義。 */
    private const AUTH_ERROR_PREFIXES = ['INV900', 'KEY100'];

    /** 認定為「訂單編號重複」的錯誤碼集合（藍新新版回 NOR10001、舊文件曾出現 LIB10003）。 */
    private const DUPLICATE_ORDER_NO_CODES = ['NOR10001', 'LIB10003'];

    /**
     * @param array<string,mixed> $rawResponse
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $rawResponse = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * 回傳 errorCode 的前綴（英文+3碼數字，如 'INV100'、'KEY100'）；非標準格式回空字串。
     *
     * 提供呼叫端做語意分群（例如 INV100xx 都是欄位格式錯誤），免去字串 match 個別代碼。
     */
    public function errorCodePrefix(): string
    {
        return preg_match('/^([A-Z]+\d{3})/', $this->errorCode, $m) === 1 ? $m[1] : '';
    }

    /**
     * 欄位格式錯誤（含必填遺漏、長度超限、字元集違規）。
     *
     * 含括：INV100xx（必填 / 格式錯誤）、INV700xx（通用欄位格式錯）。
     * 此類錯誤**重試無意義**，應引導使用者修正輸入。
     */
    public function isFieldFormatError(): bool
    {
        return in_array($this->errorCodePrefix(), self::FIELD_FORMAT_ERROR_PREFIXES, true);
    }

    /**
     * 憑證 / 簽章 / 解密錯誤（多半是 hashKey / hashIv 設定不正確）。
     *
     * 含括：INV900xx（憑證/簽章）、KEY100xx（解密失敗）。
     * 此類錯誤**重試無意義**，應直接告警 ops 修正設定。
     */
    public function isAuthError(): bool
    {
        return in_array($this->errorCodePrefix(), self::AUTH_ERROR_PREFIXES, true);
    }

    /**
     * 訂單編號重複（呼叫端常見處理：產生新訂單編號後重試）。
     */
    public function isDuplicateOrderNo(): bool
    {
        return in_array($this->errorCode, self::DUPLICATE_ORDER_NO_CODES, true);
    }
}
