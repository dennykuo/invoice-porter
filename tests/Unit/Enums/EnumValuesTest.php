<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Enums;

use InvoicePorter\Ezpay\Enums\AllowanceConfirmStatus;
use InvoicePorter\Ezpay\Enums\AllowanceTouchStatus;
use InvoicePorter\Ezpay\Enums\CarrierType;
use InvoicePorter\Ezpay\Enums\Category;
use InvoicePorter\Ezpay\Enums\CustomsClearance;
use InvoicePorter\Ezpay\Enums\DisplayFlag;
use InvoicePorter\Ezpay\Enums\InvoiceLifecycleStatus;
use InvoicePorter\Ezpay\Enums\InvoiceStatus;
use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\InvoiceType;
use InvoicePorter\Ezpay\Enums\KioskPrintFlag;
use InvoicePorter\Ezpay\Enums\PrintFlag;
use InvoicePorter\Ezpay\Enums\RespondType;
use InvoicePorter\Ezpay\Enums\SearchType;
use InvoicePorter\Ezpay\Enums\TaxType;
use InvoicePorter\Ezpay\Enums\TrackFlag;
use InvoicePorter\Ezpay\Enums\UploadStatus;
use PHPUnit\Framework\TestCase;

final class EnumValuesTest extends TestCase
{
    public function testInvoiceStatusValues(): void
    {
        $this->assertSame('0', InvoiceStatus::Pending->value);
        $this->assertSame('1', InvoiceStatus::Immediate->value);
        $this->assertSame('3', InvoiceStatus::Scheduled->value);
    }

    public function testCategoryValues(): void
    {
        $this->assertSame('B2B', Category::B2B->value);
        $this->assertSame('B2C', Category::B2C->value);
    }

    public function testTaxTypeValues(): void
    {
        $this->assertSame('1', TaxType::Taxable->value);
        $this->assertSame('2', TaxType::ZeroRate->value);
        $this->assertSame('3', TaxType::Exempt->value);
        $this->assertSame('9', TaxType::Mixed->value);
    }

    public function testCarrierTypeValues(): void
    {
        $this->assertSame('0', CarrierType::Member->value);
        $this->assertSame('1', CarrierType::Mobile->value);
        $this->assertSame('2', CarrierType::CitizenDigitalCertificate->value);
    }

    public function testPrintFlagValues(): void
    {
        $this->assertSame('Y', PrintFlag::Yes->value);
        $this->assertSame('N', PrintFlag::No->value);
    }

    public function testKioskPrintFlagValues(): void
    {
        $this->assertSame('1', KioskPrintFlag::Enabled->value);
    }

    public function testCustomsClearanceValues(): void
    {
        $this->assertSame('1', CustomsClearance::NotThroughCustoms->value);
        $this->assertSame('2', CustomsClearance::ThroughCustoms->value);
    }

    public function testSearchTypeValues(): void
    {
        $this->assertSame('0', SearchType::ByInvoiceNumber->value);
        $this->assertSame('1', SearchType::ByMerchantOrderNo->value);
    }

    public function testDisplayFlagValues(): void
    {
        $this->assertSame('1', DisplayFlag::Redirect->value);
        $this->assertSame('2', DisplayFlag::ResultUrl->value);
    }

    public function testInvoiceLifecycleStatusValues(): void
    {
        $this->assertSame('1', InvoiceLifecycleStatus::Issued->value);
        $this->assertSame('2', InvoiceLifecycleStatus::Voided->value);
    }

    public function testUploadStatusValues(): void
    {
        $this->assertSame('0', UploadStatus::NotUploaded->value);
        $this->assertSame('4', UploadStatus::ConsumerUploaded->value);
    }

    public function testAllowanceConfirmStatusValues(): void
    {
        $this->assertSame('0', AllowanceConfirmStatus::Pending->value);
        $this->assertSame('1', AllowanceConfirmStatus::Immediate->value);
    }

    public function testAllowanceTouchStatusValues(): void
    {
        $this->assertSame('C', AllowanceTouchStatus::Confirm->value);
        $this->assertSame('D', AllowanceTouchStatus::Deny->value);
    }

    public function testInvoiceTypeValues(): void
    {
        $this->assertSame('07', InvoiceType::General->value);
        $this->assertSame('08', InvoiceType::Special->value);
    }

    public function testRespondTypeValues(): void
    {
        $this->assertSame('JSON', RespondType::Json->value);
        $this->assertSame('String', RespondType::String->value);
    }

    public function testInvoiceTermValues(): void
    {
        $this->assertSame('1', InvoiceTerm::JanFeb->value);
        $this->assertSame('2', InvoiceTerm::MarApr->value);
        $this->assertSame('3', InvoiceTerm::MayJun->value);
        $this->assertSame('4', InvoiceTerm::JulAug->value);
        $this->assertSame('5', InvoiceTerm::SepOct->value);
        $this->assertSame('6', InvoiceTerm::NovDec->value);
    }

    public function testTrackFlagValues(): void
    {
        $this->assertSame('0', TrackFlag::Paused->value);
        $this->assertSame('1', TrackFlag::Active->value);
        $this->assertSame('2', TrackFlag::Disabled->value);
    }
}
