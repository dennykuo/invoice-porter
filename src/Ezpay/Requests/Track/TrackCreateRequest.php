<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Requests\Track;

use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\Enums\InvoiceType;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use InvoicePorter\Ezpay\Requests\EzpayRequest;
use InvoicePorter\Ezpay\Responses\Track\TrackCreateResponse;

/**
 * 新增字軌（EZP_Track_1.0.0 §5-1）。
 *
 * 上傳國稅局配發的發票字軌、起訖號碼至藍新；新增成功後此字軌即可被「開立發票」端點使用。
 * 預設驗 CheckCode（簽名為字軌 5 欄：CompanyId / AphabeticLetter / StartNumber / EndNumber / ManagementNo）。
 */
final class TrackCreateRequest extends EzpayRequest
{
    public function __construct(
        public readonly string $year,
        public readonly InvoiceTerm $term,
        public readonly string $aphabeticLetter,
        public readonly string $startNumber,
        public readonly string $endNumber,
        public readonly InvoiceType $type = InvoiceType::General,
    ) {
        if ($year === '' || strlen($year) !== 3 || !ctype_digit($year)) {
            throw new EzpayValidationException('year 必須為 3 位數字（民國年三碼）');
        }
        if (strlen($aphabeticLetter) !== 2 || !ctype_upper($aphabeticLetter) || !ctype_alpha($aphabeticLetter)) {
            throw new EzpayValidationException('aphabeticLetter 必須為兩個大寫英文字母');
        }
        if (strlen($startNumber) !== 8 || !ctype_digit($startNumber)) {
            throw new EzpayValidationException('startNumber 必須為 8 位數字');
        }
        if (strlen($endNumber) !== 8 || !ctype_digit($endNumber)) {
            throw new EzpayValidationException('endNumber 必須為 8 位數字');
        }
        if ((int) $startNumber > (int) $endNumber) {
            throw new EzpayValidationException('startNumber 不可大於 endNumber');
        }
    }

    public function uri(): string
    {
        return 'Api_number_management/createNumber';
    }

    public function version(): string
    {
        return '1.0';
    }

    public function responseClass(): string
    {
        return TrackCreateResponse::class;
    }

    public function checkCodeFields(): array
    {
        return ['CompanyId', 'AphabeticLetter', 'StartNumber', 'EndNumber', 'ManagementNo'];
    }

    public function checkCodeHint(): array
    {
        return [
            'AphabeticLetter' => $this->aphabeticLetter,
            'StartNumber' => $this->startNumber,
            'EndNumber' => $this->endNumber,
        ];
    }

    public function toEncryptablePayload(): array
    {
        return [
            'RespondType' => $this->respondType()->value,
            'Version' => $this->version(),
            'TimeStamp' => (string) time(),
            'Year' => $this->year,
            'Term' => $this->term->value,
            'AphabeticLetter' => $this->aphabeticLetter,
            'StartNumber' => $this->startNumber,
            'EndNumber' => $this->endNumber,
            'Type' => $this->type->value,
        ];
    }
}
