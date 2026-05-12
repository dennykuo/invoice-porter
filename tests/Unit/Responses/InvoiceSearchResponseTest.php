<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Responses;

use InvoicePorter\Ezpay\Responses\InvoiceSearchResponse;
use PHPUnit\Framework\TestCase;

final class InvoiceSearchResponseTest extends TestCase
{
    public function testCreateTimeAtParsesValidString(): void
    {
        $response = new InvoiceSearchResponse([], [
            'CreateTime' => '2026-01-01 12:00:00',
        ]);

        $dt = $response->createTimeAt();
        $this->assertNotNull($dt);
        $this->assertSame('2026-01-01 12:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testCreateTimeAtReturnsNullWhenMissing(): void
    {
        $response = new InvoiceSearchResponse([], []);
        $this->assertNull($response->createTimeAt());
    }
}
