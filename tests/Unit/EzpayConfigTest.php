<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit;

use InvoicePorter\Ezpay\Environment;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\EzpayConfig;
use PHPUnit\Framework\TestCase;

final class EzpayConfigTest extends TestCase
{
    public function testValidConstruction(): void
    {
        $config = new EzpayConfig(
            merchantId: '00000000',
            hashKey: str_repeat('a', 32),
            hashIv: str_repeat('b', 16),
        );

        $this->assertSame('00000000', $config->merchantId);
        $this->assertSame(Environment::Sandbox, $config->environment);
    }

    public function testRejectsEmptyMerchantId(): void
    {
        $this->expectException(EzpayValidationException::class);
        new EzpayConfig('', str_repeat('a', 32), str_repeat('b', 16));
    }

    public function testRejectsHashKeyOfWrongLength(): void
    {
        $this->expectException(EzpayValidationException::class);
        new EzpayConfig('M', str_repeat('a', 31), str_repeat('b', 16));
    }

    public function testRejectsHashIvOfWrongLength(): void
    {
        $this->expectException(EzpayValidationException::class);
        new EzpayConfig('M', str_repeat('a', 32), str_repeat('b', 15));
    }

    public function testRejectsNonPositiveTimeout(): void
    {
        $this->expectException(EzpayValidationException::class);
        new EzpayConfig('M', str_repeat('a', 32), str_repeat('b', 16), Environment::Sandbox, 0.0);
    }

    public function testFromEnvReadsRequiredKeys(): void
    {
        $previous = [
            $_ENV['EZPAY_MERCHANT_ID'] ?? null,
            $_ENV['EZPAY_HASH_KEY'] ?? null,
            $_ENV['EZPAY_HASH_IV'] ?? null,
            $_ENV['EZPAY_ENVIRONMENT'] ?? null,
        ];

        try {
            $_ENV['EZPAY_MERCHANT_ID'] = 'M-XYZ';
            $_ENV['EZPAY_HASH_KEY'] = str_repeat('k', 32);
            $_ENV['EZPAY_HASH_IV'] = str_repeat('i', 16);
            $_ENV['EZPAY_ENVIRONMENT'] = 'production';

            $config = EzpayConfig::fromEnv();

            $this->assertSame('M-XYZ', $config->merchantId);
            $this->assertSame(Environment::Production, $config->environment);
        } finally {
            [$mid, $hk, $iv, $envName] = $previous;
            $this->restoreEnv('EZPAY_MERCHANT_ID', $mid);
            $this->restoreEnv('EZPAY_HASH_KEY', $hk);
            $this->restoreEnv('EZPAY_HASH_IV', $iv);
            $this->restoreEnv('EZPAY_ENVIRONMENT', $envName);
        }
    }

    public function testFromEnvThrowsWhenMissing(): void
    {
        $previous = [
            $_ENV['EZPAY_FAKE_MERCHANT_ID'] ?? null,
        ];

        try {
            unset($_ENV['EZPAY_FAKE_MERCHANT_ID']);
            $this->expectException(EzpayValidationException::class);
            EzpayConfig::fromEnv('EZPAY_FAKE_');
        } finally {
            $this->restoreEnv('EZPAY_FAKE_MERCHANT_ID', $previous[0]);
        }
    }

    private function restoreEnv(string $key, ?string $previous): void
    {
        if ($previous === null) {
            unset($_ENV[$key]);
            return;
        }
        $_ENV[$key] = $previous;
    }
}
