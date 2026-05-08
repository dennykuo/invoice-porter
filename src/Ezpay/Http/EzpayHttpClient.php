<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvoicePorter\Ezpay\Exceptions\EzpayTransportException;

final class EzpayHttpClient
{
    public function __construct(private readonly ClientInterface $http)
    {
    }

    /**
     * @param array<string,string> $form
     * @return array<string,mixed>
     */
    public function postForm(string $uri, array $form): array
    {
        try {
            $response = $this->http->request('POST', $uri, [
                'form_params' => $form,
            ]);
        } catch (GuzzleException $e) {
            throw new EzpayTransportException('HTTP 連線失敗：' . $e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new EzpayTransportException("藍新回傳 HTTP {$statusCode}");
        }

        $body = (string) $response->getBody();

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EzpayTransportException('回應 JSON 解析失敗：' . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new EzpayTransportException('回應 JSON 結構錯誤');
        }

        /** @var array<string,mixed> $decoded */
        return $decoded;
    }
}
