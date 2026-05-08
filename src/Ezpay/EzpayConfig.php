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
    }

    public static function fromEnv(string $prefix = 'EZPAY_'): self
    {
        $merchantId = self::readEnv($prefix . 'MERCHANT_ID');
        $hashKey = self::readEnv($prefix . 'HASH_KEY');
        $hashIv = self::readEnv($prefix . 'HASH_IV');
        $envName = self::readEnv($prefix . 'ENVIRONMENT') ?? 'sandbox';

        if ($merchantId === null || $hashKey === null || $hashIv === null) {
            throw new EzpayValidationException(sprintf(
                '環境變數缺少 %sMERCHANT_ID / %sHASH_KEY / %sHASH_IV',
                $prefix,
                $prefix,
                $prefix,
            ));
        }

        $environment = Environment::tryFrom($envName) ?? Environment::Sandbox;

        return new self(
            merchantId: $merchantId,
            hashKey: $hashKey,
            hashIv: $hashIv,
            environment: $environment,
        );
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
