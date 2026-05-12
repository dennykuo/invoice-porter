<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Responses;

use InvoicePorter\Ezpay\Responses\EzpayResponse;
use PHPUnit\Framework\TestCase;

final class EzpayResponseTest extends TestCase
{
    public function testParseDateTimeWithValidFormat(): void
    {
        $response = $this->makeResponse(['CreateTime' => '2026-01-01 12:00:00']);

        $dt = $response->exposedParseDateTime('2026-01-01 12:00:00');

        $this->assertNotNull($dt);
        $this->assertSame('2026-01-01 12:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testParseDateTimeReturnsNullOnNull(): void
    {
        $response = $this->makeResponse([]);
        $this->assertNull($response->exposedParseDateTime(null));
    }

    public function testParseDateTimeReturnsNullOnEmptyString(): void
    {
        $response = $this->makeResponse([]);
        $this->assertNull($response->exposedParseDateTime(''));
    }

    public function testParseDateTimeReturnsNullOnMalformed(): void
    {
        $response = $this->makeResponse([]);
        $this->assertNull($response->exposedParseDateTime('wat'));
    }

    public function testParseDateTimeUsesDefaultTimezone(): void
    {
        $previous = date_default_timezone_get();
        try {
            date_default_timezone_set('Asia/Taipei');

            $response = $this->makeResponse([]);
            $dt = $response->exposedParseDateTime('2026-01-01 12:00:00');

            $this->assertNotNull($dt);
            $this->assertSame('Asia/Taipei', $dt->getTimezone()->getName());
        } finally {
            date_default_timezone_set($previous);
        }
    }

    /**
     * @param array<string,mixed> $result
     */
    private function makeResponse(array $result): EzpayResponse
    {
        return new class ([], $result) extends EzpayResponse {
            public function exposedParseDateTime(?string $value): ?\DateTimeImmutable
            {
                return $this->parseDateTime($value);
            }
        };
    }
}
