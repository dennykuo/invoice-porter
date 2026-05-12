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

    public function testRejectsPipeInName(): void
    {
        // 多個 item 會用 | 串成 ItemName 送出，name 含 | 會讓藍新把單一品項解析成多項
        // 造成靜默資料毀損（item count / price / amount 數量對不上）。
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('|');

        new InvoiceItem(name: '商品 A | 加購 B', count: 1, unit: '個', price: 100, amount: 100);
    }

    public function testRejectsPipeInUnit(): void
    {
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('|');

        new InvoiceItem(name: 'X', count: 1, unit: '個|盒', price: 100, amount: 100);
    }

    public function testRejectsNameOver30Chars(): void
    {
        // 藍新規格 ItemName Varchar(30)（中文以字元為單位，與 invalidReason 相同採 mb_strlen）
        $this->expectException(EzpayValidationException::class);
        $this->expectExceptionMessage('30');

        new InvoiceItem(
            name: str_repeat('品', 31),
            count: 1,
            unit: '個',
            price: 100,
            amount: 100,
        );
    }

    public function testAcceptsNameAt30Chars(): void
    {
        $item = new InvoiceItem(
            name: str_repeat('品', 30),
            count: 1,
            unit: '個',
            price: 100,
            amount: 100,
        );

        $this->assertSame(str_repeat('品', 30), $item->name);
    }
}
