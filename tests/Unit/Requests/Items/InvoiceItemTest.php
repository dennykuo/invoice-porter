<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests\Items;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\Items\InvoiceItem;
use PHPUnit\Framework\TestCase;

final class InvoiceItemTest extends TestCase
{
    public function testConstructWithValidArgs(): void
    {
        $item = new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 100, amount: 100);

        $this->assertSame('商品一', $item->name);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(EzpayValidationException::class);
        new InvoiceItem(name: '', count: 1, unit: '個', price: 100, amount: 100);
    }

    public function testRejectsZeroCount(): void
    {
        $this->expectException(EzpayValidationException::class);
        new InvoiceItem(name: 'X', count: 0, unit: '個', price: 100, amount: 100);
    }

    public function testRejectsNegativePrice(): void
    {
        $this->expectException(EzpayValidationException::class);
        new InvoiceItem(name: 'X', count: 1, unit: '個', price: -1, amount: 100);
    }
}
