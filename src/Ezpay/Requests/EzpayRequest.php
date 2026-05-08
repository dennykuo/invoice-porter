<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests;

use InvoicePorter\Ezpay\Enums\RespondType;

/**
 * 所有 EZPay API 請求的抽象基底類別。
 *
 * 子類別需提供：
 * - uri()：API 端點路徑
 * - version()：藍新 API 版本字串
 * - toEncryptablePayload()：要 AES 加密的純參數陣列
 * - responseClass()：對應 Response DTO 類別
 *
 * 並可覆寫：
 * - checkCodeFields()：回傳實際參與 CheckCode 計算的欄位名稱（小寫 keys 映射）；null 代表不驗
 * - checkCodeHint()：當回應缺少 CheckCode 計算所需欄位時用來補齊（例如使用者建構時提供的 randomNum）
 */
abstract class EzpayRequest
{
    abstract public function uri(): string;

    abstract public function version(): string;

    /**
     * @return array<string,scalar|null>
     */
    abstract public function toEncryptablePayload(): array;

    /**
     * @return class-string
     */
    abstract public function responseClass(): string;

    public function respondType(): RespondType
    {
        return RespondType::Json;
    }

    /**
     * 回傳 EZPay 為此 Request 計算 CheckCode 用到的欄位名稱（藍新文件附件二的 5 欄）。
     *
     * @return list<string>|null null 代表此 Request 不驗 CheckCode
     */
    public function checkCodeFields(): ?array
    {
        return null;
    }

    /**
     * 提供 Request 自帶的欄位（例如使用者已知的 randomNum / merchantOrderNo），
     * 在 Response 缺少對應欄位時用來補齊 CheckCode 計算。
     *
     * @return array<string,string|int>
     */
    public function checkCodeHint(): array
    {
        return [];
    }
}
