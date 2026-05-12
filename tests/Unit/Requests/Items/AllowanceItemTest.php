<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests\Items;

use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\Items\AllowanceItem;
use PHPUnit\Framework\TestCase;

final class AllowanceItemTest extends TestCase
{
    public function testConstructWithValidArgs(): void
    {
        $item = new AllowanceItem(
            name: '商品一',
            count: 1,
            unit: '個',
            price: 95,
            amount: 95,
            taxAmount: 5,
        );

        $this->assertSame('商品一', $item->name);
    }

    public function testRejectsPipeInName(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('|');

        new AllowanceItem(name: '商品 A | 加購 B', count: 1, unit: '個', price: 100, amount: 100);
    }

    public function testRejectsPipeInUnit(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('|');

        new AllowanceItem(name: 'X', count: 1, unit: '個|盒', price: 100, amount: 100);
    }

    public function testRejectsNameOver30Chars(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('30');

        new AllowanceItem(
            name: str_repeat('品', 31),
            count: 1,
            unit: '個',
            price: 100,
            amount: 100,
        );
    }
}
