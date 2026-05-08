<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit;

use InvoicePorter\Ezpay\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    public function testSandboxBaseUrl(): void
    {
        $this->assertSame('https://cinv.ezpay.com.tw/Api/', Environment::Sandbox->baseUrl());
    }

    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://inv.ezpay.com.tw/Api/', Environment::Production->baseUrl());
    }

    public function testCanRoundTripFromString(): void
    {
        $this->assertSame(Environment::Sandbox, Environment::from('sandbox'));
        $this->assertSame(Environment::Production, Environment::from('production'));
        $this->assertNull(Environment::tryFrom('invalid'));
    }
}
