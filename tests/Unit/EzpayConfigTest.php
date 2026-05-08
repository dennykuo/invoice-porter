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

    public function testCompanyCredentialsAreOptional(): void
    {
        // 不帶字軌欄位仍可建構（向後相容）
        $config = new EzpayConfig(
            merchantId: '00000000',
            hashKey: str_repeat('a', 32),
            hashIv: str_repeat('b', 16),
        );

        $this->assertNull($config->companyId);
        $this->assertNull($config->companyHashKey);
        $this->assertNull($config->companyHashIv);
    }

    public function testCompanyCredentialsCanBeProvided(): void
    {
        $config = new EzpayConfig(
            merchantId: '00000000',
            hashKey: str_repeat('a', 32),
            hashIv: str_repeat('b', 16),
            companyId: '12345678',
            companyHashKey: str_repeat('c', 32),
            companyHashIv: str_repeat('d', 16),
        );

        $this->assertSame('12345678', $config->companyId);
        $this->assertSame(str_repeat('c', 32), $config->companyHashKey);
        $this->assertSame(str_repeat('d', 16), $config->companyHashIv);
    }

    public function testRejectsCompanyHashKeyOfWrongLength(): void
    {
        $this->expectException(EzpayValidationException::class);
        new EzpayConfig(
            merchantId: '00000000',
            hashKey: str_repeat('a', 32),
            hashIv: str_repeat('b', 16),
            companyHashKey: str_repeat('c', 31),
        );
    }

    public function testRejectsCompanyHashIvOfWrongLength(): void
    {
        $this->expectException(EzpayValidationException::class);
        new EzpayConfig(
            merchantId: '00000000',
            hashKey: str_repeat('a', 32),
            hashIv: str_repeat('b', 16),
            companyHashIv: str_repeat('d', 15),
        );
    }

    public function testRequireCompanyCredentialsThrowsWhenMissing(): void
    {
        $config = new EzpayConfig(
            merchantId: '00000000',
            hashKey: str_repeat('a', 32),
            hashIv: str_repeat('b', 16),
        );

        $this->expectException(EzpayValidationException::class);
        $config->requireCompanyCredentials();
    }

    public function testRequireCompanyCredentialsPassesWhenAllProvided(): void
    {
        $config = new EzpayConfig(
            merchantId: '00000000',
            hashKey: str_repeat('a', 32),
            hashIv: str_repeat('b', 16),
            companyId: '12345678',
            companyHashKey: str_repeat('c', 32),
            companyHashIv: str_repeat('d', 16),
        );

        $config->requireCompanyCredentials();
        $this->assertSame('12345678', $config->companyId);
    }

    public function testFromEnvReadsCompanyKeysWhenAvailable(): void
    {
        $previous = [
            $_ENV['EZPAY_MERCHANT_ID'] ?? null,
            $_ENV['EZPAY_HASH_KEY'] ?? null,
            $_ENV['EZPAY_HASH_IV'] ?? null,
            $_ENV['EZPAY_COMPANY_ID'] ?? null,
            $_ENV['EZPAY_COMPANY_HASH_KEY'] ?? null,
            $_ENV['EZPAY_COMPANY_HASH_IV'] ?? null,
        ];

        try {
            $_ENV['EZPAY_MERCHANT_ID'] = 'M-XYZ';
            $_ENV['EZPAY_HASH_KEY'] = str_repeat('k', 32);
            $_ENV['EZPAY_HASH_IV'] = str_repeat('i', 16);
            $_ENV['EZPAY_COMPANY_ID'] = 'C-456';
            $_ENV['EZPAY_COMPANY_HASH_KEY'] = str_repeat('p', 32);
            $_ENV['EZPAY_COMPANY_HASH_IV'] = str_repeat('q', 16);

            $config = EzpayConfig::fromEnv();

            $this->assertSame('C-456', $config->companyId);
            $this->assertSame(str_repeat('p', 32), $config->companyHashKey);
            $this->assertSame(str_repeat('q', 16), $config->companyHashIv);
        } finally {
            [$mid, $hk, $iv, $cid, $chk, $civ] = $previous;
            $this->restoreEnv('EZPAY_MERCHANT_ID', $mid);
            $this->restoreEnv('EZPAY_HASH_KEY', $hk);
            $this->restoreEnv('EZPAY_HASH_IV', $iv);
            $this->restoreEnv('EZPAY_COMPANY_ID', $cid);
            $this->restoreEnv('EZPAY_COMPANY_HASH_KEY', $chk);
            $this->restoreEnv('EZPAY_COMPANY_HASH_IV', $civ);
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
