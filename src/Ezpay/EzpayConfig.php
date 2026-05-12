<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;

final class EzpayConfig
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $hashKey,
        public readonly string $hashIv,
        public readonly Environment $environment = Environment::Sandbox,
        public readonly float $timeoutSeconds = 10.0,
        public readonly float $connectTimeoutSeconds = 5.0,
        public readonly ?string $companyId = null,
        public readonly ?string $companyHashKey = null,
        public readonly ?string $companyHashIv = null,
    ) {
        if ($this->merchantId === '') {
            throw new EzpayValidationException('merchantId 不可為空');
        }

        if (strlen($this->hashKey) !== 32) {
            throw new EzpayValidationException('hashKey 必須為 32 個字元');
        }

        if (strlen($this->hashIv) !== 16) {
            throw new EzpayValidationException('hashIv 必須為 16 個字元');
        }

        if ($this->timeoutSeconds <= 0) {
            throw new EzpayValidationException('timeoutSeconds 必須大於 0');
        }

        if ($this->connectTimeoutSeconds <= 0) {
            throw new EzpayValidationException('connectTimeoutSeconds 必須大於 0');
        }

        if ($companyHashKey !== null && strlen($companyHashKey) !== 32) {
            throw new EzpayValidationException('companyHashKey 必須為 32 個字元');
        }

        if ($companyHashIv !== null && strlen($companyHashIv) !== 16) {
            throw new EzpayValidationException('companyHashIv 必須為 16 個字元');
        }
    }

    public static function fromEnv(string $prefix = 'EZPAY_'): self
    {
        $merchantId = self::readEnv($prefix . 'MERCHANT_ID');
        $hashKey = self::readEnv($prefix . 'HASH_KEY');
        $hashIv = self::readEnv($prefix . 'HASH_IV');

        if ($merchantId === null || $hashKey === null || $hashIv === null) {
            throw new EzpayValidationException(sprintf(
                '環境變數缺少 %sMERCHANT_ID / %sHASH_KEY / %sHASH_IV',
                $prefix,
                $prefix,
                $prefix,
            ));
        }

        $cfg = [
            'merchant_id' => $merchantId,
            'hash_key' => $hashKey,
            'hash_iv' => $hashIv,
            'environment' => self::readEnv($prefix . 'ENVIRONMENT') ?? 'sandbox',
            'company_id' => self::readEnv($prefix . 'COMPANY_ID'),
            'company_hash_key' => self::readEnv($prefix . 'COMPANY_HASH_KEY'),
            'company_hash_iv' => self::readEnv($prefix . 'COMPANY_HASH_IV'),
        ];

        return self::fromArray($cfg);
    }

    /**
     * Array-based factory，方便 Laravel `config:cache` 後使用：
     *
     * ```php
     * EzpayConfig::fromArray(config('ezpay'));
     * ```
     *
     * 只接受 snake_case keys（對齊 Laravel `config/*.php` 慣例），不收 camelCase 別名以保 type-safety：
     *
     * - **必填**：`merchant_id`、`hash_key`、`hash_iv`
     * - **可選**：`environment`、`timeout_seconds`、`connect_timeout_seconds`
     * - **可選（字軌 API）**：`company_id`、`company_hash_key`、`company_hash_iv`
     *
     * Unknown keys 會被寬容忽略，保留使用者擴充自家 config 欄位的空間（如 `'logging' => true`）。
     * 注意：不會 fallback 讀環境變數 — 想用 env 請改用 `fromEnv()`。
     *
     * @param array<string,mixed> $cfg
     */
    public static function fromArray(array $cfg): self
    {
        $missing = [];
        foreach (['merchant_id', 'hash_key', 'hash_iv'] as $required) {
            $value = $cfg[$required] ?? null;
            if (!is_string($value) || $value === '') {
                $missing[] = $required;
            }
        }
        if ($missing !== []) {
            throw new EzpayValidationException(sprintf(
                'EzpayConfig::fromArray 缺少必填 key：%s',
                implode(', ', $missing),
            ));
        }

        /** @var string $merchantId */
        $merchantId = $cfg['merchant_id'];
        /** @var string $hashKey */
        $hashKey = $cfg['hash_key'];
        /** @var string $hashIv */
        $hashIv = $cfg['hash_iv'];

        $environment = Environment::Sandbox;
        $envValue = $cfg['environment'] ?? null;
        if (is_string($envValue) && $envValue !== '') {
            $environment = Environment::tryFrom($envValue) ?? Environment::Sandbox;
        }

        $defaults = [
            'timeout_seconds' => 10.0,
            'connect_timeout_seconds' => 5.0,
        ];
        $timeoutSeconds = isset($cfg['timeout_seconds']) && is_numeric($cfg['timeout_seconds'])
            ? (float) $cfg['timeout_seconds']
            : $defaults['timeout_seconds'];
        $connectTimeoutSeconds = isset($cfg['connect_timeout_seconds']) && is_numeric($cfg['connect_timeout_seconds'])
            ? (float) $cfg['connect_timeout_seconds']
            : $defaults['connect_timeout_seconds'];

        return new self(
            merchantId: $merchantId,
            hashKey: $hashKey,
            hashIv: $hashIv,
            environment: $environment,
            timeoutSeconds: $timeoutSeconds,
            connectTimeoutSeconds: $connectTimeoutSeconds,
            companyId: self::optionalString($cfg, 'company_id'),
            companyHashKey: self::optionalString($cfg, 'company_hash_key'),
            companyHashIv: self::optionalString($cfg, 'company_hash_iv'),
        );
    }

    /**
     * @param array<string,mixed> $cfg
     */
    private static function optionalString(array $cfg, string $key): ?string
    {
        $value = $cfg[$key] ?? null;
        if (!is_string($value) || $value === '') {
            return null;
        }
        return $value;
    }

    /**
     * 確認字軌 API 所需的會員（公司）層級憑證皆已設定，否則拋例外。
     *
     * `EzpayTrackClient` 在 constructor 呼叫此方法以早期失敗，
     * 避免使用者只在發送 request 時才得知 config 不齊。
     */
    public function requireCompanyCredentials(): void
    {
        if ($this->companyId === null || $this->companyHashKey === null || $this->companyHashIv === null) {
            throw new EzpayValidationException(
                'EzpayTrackClient 需要 companyId / companyHashKey / companyHashIv 三者皆不為 null',
            );
        }
    }

    private static function readEnv(string $key): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
