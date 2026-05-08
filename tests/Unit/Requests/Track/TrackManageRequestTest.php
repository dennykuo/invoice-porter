<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Requests\Track;

use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\Track\TrackManageRequest;
use InvoicePorter\Ezpay\Responses\Track\TrackManageResponse;
use PHPUnit\Framework\TestCase;

final class TrackManageRequestTest extends TestCase
{
    public function testDefaultsDoNotVerifyCheckCode(): void
    {
        $request = new TrackManageRequest(
            managementNo: 'MN-001',
            flag: TrackFlag::Active,
        );

        $this->assertSame('Api_number_management/manageNumber', $request->uri());
        $this->assertSame('1.0', $request->version());
        $this->assertSame(TrackManageResponse::class, $request->responseClass());
        $this->assertNull($request->checkCodeFields());
    }

    public function testExpectCheckCodeReturnsTrackFields(): void
    {
        $request = new TrackManageRequest(
            managementNo: 'MN-001',
            flag: TrackFlag::Disabled,
            expectCheckCode: true,
        );

        $this->assertSame(
            ['CompanyId', 'AphabeticLetter', 'StartNumber', 'EndNumber', 'ManagementNo'],
            $request->checkCodeFields(),
        );
        $this->assertSame('MN-001', $request->checkCodeHint()['ManagementNo']);
    }

    public function testPayloadContainsManagementNoAndFlag(): void
    {
        $request = new TrackManageRequest(
            managementNo: 'MN-002',
            flag: TrackFlag::Paused,
        );

        $payload = $request->toEncryptablePayload();
        $this->assertSame('MN-002', $payload['ManagementNo']);
        $this->assertSame('0', $payload['Flag']);
    }

    public function testRejectsEmptyManagementNo(): void
    {
        $this->expectException(EzpayValidationException::class);
        new TrackManageRequest(managementNo: '', flag: TrackFlag::Active);
    }
}
