<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests\Track;

use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\Track\TrackSearchRequest;
use InvoicePorter\Ezpay\Responses\Track\TrackSearchResponse;
use PHPUnit\Framework\TestCase;

final class TrackSearchRequestTest extends TestCase
{
    public function testAllOptionalFieldsAreNullable(): void
    {
        $request = new TrackSearchRequest();

        $this->assertSame('Api_number_management/searchNumber', $request->uri());
        $this->assertSame('1.0', $request->version());
        $this->assertSame(TrackSearchResponse::class, $request->responseClass());
        $this->assertNull($request->checkCodeFields());
    }

    public function testPayloadOmitsNullsAsEmptyStrings(): void
    {
        $request = new TrackSearchRequest(
            year: '115',
            term: InvoiceTerm::MayJun,
        );

        $payload = $request->toEncryptablePayload();
        $this->assertSame('115', $payload['Year']);
        $this->assertSame('3', $payload['Term']);
        $this->assertSame('', $payload['Flag']);
        $this->assertSame('', $payload['ManagementNo']);
    }

    public function testPayloadIncludesAllFiltersWhenProvided(): void
    {
        $request = new TrackSearchRequest(
            year: '115',
            term: InvoiceTerm::JulAug,
            flag: TrackFlag::Active,
            managementNo: 'MN-XYZ',
        );

        $payload = $request->toEncryptablePayload();
        $this->assertSame('4', $payload['Term']);
        $this->assertSame('1', $payload['Flag']);
        $this->assertSame('MN-XYZ', $payload['ManagementNo']);
    }

    public function testExpectCheckCodeOpensVerification(): void
    {
        $request = new TrackSearchRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            expectCheckCode: true,
        );

        $this->assertSame(
            ['CompanyId', 'AphabeticLetter', 'StartNumber', 'EndNumber', 'ManagementNo'],
            $request->checkCodeFields(),
        );
    }

    public function testRejectsYearWithWrongFormat(): void
    {
        $this->expectException(EzpayValidationException::class);
        new TrackSearchRequest(year: '2026');
    }
}
