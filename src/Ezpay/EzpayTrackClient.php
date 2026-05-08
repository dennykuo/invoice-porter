<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay;

use GuzzleHttp\HandlerStack;
use InvoicePorter\Ezpay\Crypto\AesCryptor;
use InvoicePorter\Ezpay\Crypto\SignatureVerifier;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayCheckCodeException;
use InvoicePorter\Ezpay\Exceptions\EzpayTransportException;
use InvoicePorter\Ezpay\Http\EzpayHttpClient;
use InvoicePorter\Ezpay\Http\HttpClientFactory;
use InvoicePorter\Ezpay\Requests\EzpayRequest;
use InvoicePorter\Ezpay\Requests\Track\TrackCreateRequest;
use InvoicePorter\Ezpay\Requests\Track\TrackManageRequest;
use InvoicePorter\Ezpay\Requests\Track\TrackSearchRequest;
use InvoicePorter\Ezpay\Responses\EzpayResponse;
use InvoicePorter\Ezpay\Responses\Track\TrackCreateResponse;
use InvoicePorter\Ezpay\Responses\Track\TrackManageResponse;
use InvoicePorter\Ezpay\Responses\Track\TrackSearchResponse;

/**
 * 藍新「電子發票字軌管理」（EZP_Track_1.0.0）API facade。
 *
 * 字軌 API 屬會員（公司）層級，與發票 API（商店層級）使用不同金鑰。
 * 因此本 client 從 `EzpayConfig` 取 `companyId` / `companyHashKey` / `companyHashIv` 三欄，
 * 並在 constructor 呼叫 `requireCompanyCredentials()` 早期失敗。
 *
 * 與 `EzpayInvoiceClient` 相同：AES-256-CBC + PKCS7 32-byte padding、CheckCode SHA256，
 * 但 envelope 第一欄改為 `CompanyID_`，CheckCode 第一個欄位名也改為 `CompanyId`（注意大小寫）。
 */
final class EzpayTrackClient
{
    private readonly EzpayHttpClient $http;
    private readonly AesCryptor $cryptor;
    private readonly SignatureVerifier $verifier;

    public function __construct(
        private readonly EzpayConfig $config,
        ?EzpayHttpClient $http = null,
        ?AesCryptor $cryptor = null,
        ?SignatureVerifier $verifier = null,
        ?HandlerStack $handlerStack = null,
    ) {
        $config->requireCompanyCredentials();

        $this->http = $http ?? new EzpayHttpClient(HttpClientFactory::create($config, $handlerStack));
        $this->cryptor = $cryptor ?? new AesCryptor((string) $config->companyHashKey, (string) $config->companyHashIv);
        $this->verifier = $verifier ?? new SignatureVerifier((string) $config->companyHashKey, (string) $config->companyHashIv);
    }

    public function trackCreate(TrackCreateRequest $request): TrackCreateResponse
    {
        /** @var TrackCreateResponse $response */
        $response = $this->send($request);
        return $response;
    }

    public function trackManage(TrackManageRequest $request): TrackManageResponse
    {
        /** @var TrackManageResponse $response */
        $response = $this->send($request);
        return $response;
    }

    public function trackSearch(TrackSearchRequest $request): TrackSearchResponse
    {
        /** @var TrackSearchResponse $response */
        $response = $this->send($request);
        return $response;
    }

    private function send(EzpayRequest $request): EzpayResponse
    {
        $payload = $request->toEncryptablePayload();
        $encrypted = $this->cryptor->encrypt(http_build_query($payload));

        $envelope = $this->http->postForm($request->uri(), [
            'CompanyID_' => (string) $this->config->companyId,
            'PostData_' => $encrypted,
        ]);

        return $this->buildResponse($request, $envelope);
    }

    /**
     * @param array<string,mixed> $envelope
     */
    private function buildResponse(EzpayRequest $request, array $envelope): EzpayResponse
    {
        $status = $envelope['Status'] ?? null;
        $message = $envelope['Message'] ?? '';
        $rawResult = $envelope['Result'] ?? null;

        if ($status !== 'SUCCESS') {
            $code = is_string($status) ? $status : 'UNKNOWN';
            $msg = is_string($message) ? $message : '';
            throw new EzpayApiException($code, $msg, $envelope);
        }

        $result = $this->decodeResult($rawResult);

        $responseClass = $request->responseClass();
        /** @var EzpayResponse $response */
        $response = new $responseClass($envelope, $result);

        $this->maybeVerifyCheckCode($request, $result);

        return $response;
    }

    /**
     * @param mixed $rawResult
     * @return array<string,mixed>
     */
    private function decodeResult(mixed $rawResult): array
    {
        if (is_array($rawResult)) {
            /** @var array<string,mixed> $rawResult */
            return $rawResult;
        }

        if (!is_string($rawResult) || $rawResult === '') {
            return [];
        }

        try {
            $decoded = json_decode($rawResult, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EzpayTransportException('Result JSON 解析失敗：' . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            return [];
        }

        /** @var array<string,mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<string,mixed> $result
     */
    private function maybeVerifyCheckCode(EzpayRequest $request, array $result): void
    {
        $fields = $request->checkCodeFields();
        if ($fields === null) {
            return;
        }

        $expected = $result['CheckCode'] ?? null;
        if (!is_string($expected) || $expected === '') {
            throw new EzpayCheckCodeException('回應缺少 CheckCode，無法驗證');
        }

        $hint = $request->checkCodeHint();
        $values = ['CompanyId' => (string) $this->config->companyId];

        foreach ($fields as $field) {
            if ($field === 'CompanyId') {
                continue;
            }
            $value = $result[$field] ?? $hint[$field] ?? null;
            if ($value === null || $value === '') {
                throw new EzpayCheckCodeException("CheckCode 計算缺少欄位：{$field}");
            }
            $values[$field] = is_scalar($value) ? (string) $value : '';
        }

        $this->verifier->verify($values, $expected);
    }
}
