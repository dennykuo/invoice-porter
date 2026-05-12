<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Responses;

use InvoicePorter\Ezpay\Responses\AllowanceInvalidResponse;
use InvoicePorter\Ezpay\Responses\AllowanceIssueResponse;
use InvoicePorter\Ezpay\Responses\AllowanceTouchIssueResponse;
use InvoicePorter\Ezpay\Responses\InvoiceInvalidResponse;
use PHPUnit\Framework\TestCase;

/**
 * 集中覆蓋剩餘 4 個 Response 的時間欄位 happy path，避免每個 Response 都另開檔案。
 *
 * `InvoiceIssueResponse` 與 `InvoiceSearchResponse` 已各自有獨立測試檔。
 */
final class CreateTimeAtTest extends TestCase
{
    public function testInvoiceInvalidResponseCreateTimeAt(): void
    {
        $response = new InvoiceInvalidResponse([], [
            'CreateTime' => '2026-01-01 12:00:00',
        ]);

        $dt = $response->createTimeAt();
        $this->assertNotNull($dt);
        $this->assertSame('2026-01-01 12:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testAllowanceIssueResponseCreateTimeAt(): void
    {
        $response = new AllowanceIssueResponse([], [
            'CreateTime' => '2026-01-01 12:00:00',
        ]);

        $dt = $response->createTimeAt();
        $this->assertNotNull($dt);
        $this->assertSame('2026-01-01 12:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testAllowanceTouchIssueResponseCreateTimeAt(): void
    {
        $response = new AllowanceTouchIssueResponse([], [
            'CreateTime' => '2026-01-01 12:00:00',
        ]);

        $dt = $response->createTimeAt();
        $this->assertNotNull($dt);
        $this->assertSame('2026-01-01 12:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testAllowanceInvalidResponseInvalidTimeAt(): void
    {
        $response = new AllowanceInvalidResponse([], [
            'CreateTime' => '2026-01-01 12:00:00',
        ]);

        $dt = $response->invalidTimeAt();
        $this->assertNotNull($dt);
        $this->assertSame('2026-01-01 12:00:00', $dt->format('Y-m-d H:i:s'));
    }
}
