<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests\Track;

use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\InvoiceType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\Track\TrackCreateRequest;
use InvoicePorter\Ezpay\Responses\Track\TrackCreateResponse;
use PHPUnit\Framework\TestCase;

final class TrackCreateRequestTest extends TestCase
{
    public function testValidConstructionExposesUriVersionAndCheckCodeFields(): void
    {
        $request = new TrackCreateRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            aphabeticLetter: 'AB',
            startNumber: '00000000',
            endNumber: '00000049',
        );

        $this->assertSame('Api_number_management/createNumber', $request->uri());
        $this->assertSame('1.0', $request->version());
        $this->assertSame(TrackCreateResponse::class, $request->responseClass());
        $this->assertSame(
            ['CompanyId', 'AphabeticLetter', 'StartNumber', 'EndNumber', 'ManagementNo'],
            $request->checkCodeFields(),
        );
    }

    public function testPayloadContainsRequiredKeys(): void
    {
        $request = new TrackCreateRequest(
            year: '115',
            term: InvoiceTerm::MarApr,
            aphabeticLetter: 'CD',
            startNumber: '00000050',
            endNumber: '00000099',
            type: InvoiceType::Special,
        );

        $payload = $request->toEncryptablePayload();

        $this->assertSame('JSON', $payload['RespondType']);
        $this->assertSame('1.0', $payload['Version']);
        $this->assertSame('115', $payload['Year']);
        $this->assertSame('2', $payload['Term']);
        $this->assertSame('CD', $payload['AphabeticLetter']);
        $this->assertSame('00000050', $payload['StartNumber']);
        $this->assertSame('00000099', $payload['EndNumber']);
        $this->assertSame('08', $payload['Type']);
    }

    public function testCheckCodeHintCarriesRequestFields(): void
    {
        $request = new TrackCreateRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            aphabeticLetter: 'AB',
            startNumber: '00000000',
            endNumber: '00000049',
        );

        $hint = $request->checkCodeHint();
        $this->assertSame('AB', $hint['AphabeticLetter']);
        $this->assertSame('00000000', $hint['StartNumber']);
        $this->assertSame('00000049', $hint['EndNumber']);
    }

    public function testRejectsYearWithWrongLength(): void
    {
        $this->expectException(EzpayValidationException::class);
        new TrackCreateRequest(
            year: '2026',
            term: InvoiceTerm::JanFeb,
            aphabeticLetter: 'AB',
            startNumber: '00000000',
            endNumber: '00000049',
        );
    }

    public function testRejectsAphabeticLetterWithWrongCase(): void
    {
        $this->expectException(EzpayValidationException::class);
        new TrackCreateRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            aphabeticLetter: 'ab',
            startNumber: '00000000',
            endNumber: '00000049',
        );
    }

    public function testRejectsStartNumberLargerThanEndNumber(): void
    {
        $this->expectException(EzpayValidationException::class);
        new TrackCreateRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            aphabeticLetter: 'AB',
            startNumber: '00000099',
            endNumber: '00000050',
        );
    }

    public function testRejectsNonDigitNumbers(): void
    {
        $this->expectException(EzpayValidationException::class);
        new TrackCreateRequest(
            year: '115',
            term: InvoiceTerm::JanFeb,
            aphabeticLetter: 'AB',
            startNumber: 'AAAAAAAA',
            endNumber: '00000049',
        );
    }
}
