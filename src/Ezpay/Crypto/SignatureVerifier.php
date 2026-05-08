<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Crypto;

use InvoicePorter\Ezpay\Exceptions\EzpayCheckCodeException;

final class SignatureVerifier
{
    public function __construct(
        private readonly string $hashKey,
        private readonly string $hashIv,
    ) {
    }

    /**
     * 依藍新 EZPay 文件附件二計算 CheckCode：
     * 1. 將 InvoiceTransNo / MerchantID / MerchantOrderNo / RandomNum / TotalAmt 五欄做 ksort
     * 2. http_build_query 後前後夾上 HashIV 與 HashKey
     * 3. SHA256 後轉大寫
     *
     * @param array<string,string|int> $fields
     */
    public function compute(array $fields): string
    {
        ksort($fields);
        $payload = http_build_query($fields);
        $signed = "HashIV={$this->hashIv}&{$payload}&HashKey={$this->hashKey}";

        return strtoupper(hash('sha256', $signed));
    }

    /**
     * @param array<string,string|int> $fields
     */
    public function verify(array $fields, string $expected): void
    {
        $actual = $this->compute($fields);

        if (!hash_equals($expected, $actual)) {
            throw new EzpayCheckCodeException('CheckCode 驗證不通過');
        }
    }
}
