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
use InvoicePorter\Ezpay\Requests\AllowanceInvalidRequest;
use InvoicePorter\Ezpay\Requests\AllowanceIssueRequest;
use InvoicePorter\Ezpay\Requests\AllowanceTouchIssueRequest;
use InvoicePorter\Ezpay\Requests\EzpayRequest;
use InvoicePorter\Ezpay\Requests\InvoiceInvalidRequest;
use InvoicePorter\Ezpay\Requests\InvoiceIssueRequest;
use InvoicePorter\Ezpay\Requests\InvoiceSearchRequest;
use InvoicePorter\Ezpay\Requests\InvoiceTouchIssueRequest;
use InvoicePorter\Ezpay\Responses\AllowanceInvalidResponse;
use InvoicePorter\Ezpay\Responses\AllowanceIssueResponse;
use InvoicePorter\Ezpay\Responses\AllowanceTouchIssueResponse;
use InvoicePorter\Ezpay\Responses\EzpayResponse;
use InvoicePorter\Ezpay\Responses\InvoiceInvalidResponse;
use InvoicePorter\Ezpay\Responses\InvoiceIssueResponse;
use InvoicePorter\Ezpay\Responses\InvoiceSearchResponse;
use InvoicePorter\Ezpay\Responses\InvoiceTouchIssueResponse;
use InvoicePorter\Ezpay\Support\RedirectFormBuilder;

final class EzpayInvoiceClient
{
    public function __construct(
        private readonly EzpayConfig $config,
        ?EzpayHttpClient $http = null,
        ?AesCryptor $cryptor = null,
        ?SignatureVerifier $verifier = null,
        ?HandlerStack $handlerStack = null,
    ) {
        $this->http = $http ?? new EzpayHttpClient(HttpClientFactory::create($config, $handlerStack));
        $this->cryptor = $cryptor ?? new AesCryptor($config->hashKey, $config->hashIv);
        $this->verifier = $verifier ?? new SignatureVerifier($config->hashKey, $config->hashIv);
    }

    private readonly EzpayHttpClient $http;
    private readonly AesCryptor $cryptor;
    private readonly SignatureVerifier $verifier;

    public function issue(InvoiceIssueRequest $request): InvoiceIssueResponse
    {
        /** @var InvoiceIssueResponse $response */
        $response = $this->send($request);
        return $response;
    }

    public function touchIssue(InvoiceTouchIssueRequest $request): InvoiceTouchIssueResponse
    {
        /** @var InvoiceTouchIssueResponse $response */
        $response = $this->send($request);
        return $response;
    }

    public function invalid(InvoiceInvalidRequest $request): InvoiceInvalidResponse
    {
        /** @var InvoiceInvalidResponse $response */
        $response = $this->send($request);
        return $response;
    }

    public function search(InvoiceSearchRequest $request): InvoiceSearchResponse
    {
        /** @var InvoiceSearchResponse $response */
        $response = $this->send($request);
        return $response;
    }

    public function issueAllowance(AllowanceIssueRequest $request): AllowanceIssueResponse
    {
        /** @var AllowanceIssueResponse $response */
        $response = $this->send($request);
        return $response;
    }

    public function touchAllowance(AllowanceTouchIssueRequest $request): AllowanceTouchIssueResponse
    {
        /** @var AllowanceTouchIssueResponse $response */
        $response = $this->send($request);
        return $response;
    }

    public function invalidAllowance(AllowanceInvalidRequest $request): AllowanceInvalidResponse
    {
        /** @var AllowanceInvalidResponse $response */
        $response = $this->send($request);
        return $response;
    }

    /**
     * 為 search 轉址（DisplayFlag=1）產生自動 submit 的 HTML 字串。
     * 使用者可在 Controller 內 echo 或 return Response。
     */
    public function searchRedirectHtml(InvoiceSearchRequest $request): string
    {
        $payload = $request->toEncryptablePayload();
        $encrypted = $this->cryptor->encrypt(http_build_query($payload));
        $url = $this->config->environment->baseUrl() . $request->uri();

        return RedirectFormBuilder::build($url, $this->config->merchantId, $encrypted);
    }

    /**
     * @deprecated 請改用 searchRedirectHtml() 方便框架包裝。此函式會 echo + exit。
     * @return never
     */
    public function searchRedirectExit(InvoiceSearchRequest $request): void
    {
        echo $this->searchRedirectHtml($request);
        exit;
    }

    private function send(EzpayRequest $request): EzpayResponse
    {
        $payload = $request->toEncryptablePayload();
        $encrypted = $this->cryptor->encrypt(http_build_query($payload));

        $envelope = $this->http->postForm($request->uri(), [
            'MerchantID_' => $this->config->merchantId,
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
        $values = ['MerchantID' => $this->config->merchantId];

        foreach ($fields as $field) {
            if ($field === 'MerchantID') {
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
