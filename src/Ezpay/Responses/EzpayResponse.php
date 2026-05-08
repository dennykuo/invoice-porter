<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses;

abstract class EzpayResponse
{
    /**
     * @param array<string,mixed> $envelope    藍新外層 JSON（Status / Message / Result）
     * @param array<string,mixed> $result      Result 內容（已 json_decode 為 array）
     */
    public function __construct(
        public readonly array $envelope,
        public readonly array $result,
    ) {
    }

    public function status(): string
    {
        $status = $this->envelope['Status'] ?? '';
        return is_string($status) ? $status : '';
    }

    public function message(): string
    {
        $message = $this->envelope['Message'] ?? '';
        return is_string($message) ? $message : '';
    }

    /**
     * @return array<string,mixed>
     */
    public function rawResponse(): array
    {
        return $this->envelope;
    }

    public function isSuccess(): bool
    {
        return $this->status() === 'SUCCESS';
    }

    protected function string(string $key): ?string
    {
        $value = $this->result[$key] ?? null;
        if ($value === null) {
            return null;
        }
        return is_scalar($value) ? (string) $value : null;
    }

    protected function requireString(string $key): string
    {
        $value = $this->string($key);
        if ($value === null) {
            return '';
        }
        return $value;
    }
}
