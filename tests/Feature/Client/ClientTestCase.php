<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvoicePorter\Ezpay\Crypto\AesCryptor;
use InvoicePorter\Ezpay\Crypto\SignatureVerifier;
use InvoicePorter\Ezpay\Environment;
use InvoicePorter\Ezpay\EzpayConfig;
use InvoicePorter\Ezpay\EzpayInvoiceClient;
use InvoicePorter\Ezpay\Http\EzpayHttpClient;
use PHPUnit\Framework\TestCase;

abstract class ClientTestCase extends TestCase
{
    protected const HASH_KEY = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    protected const HASH_IV = 'bbbbbbbbbbbbbbbb';
    protected const MERCHANT_ID = '00000000';

    /** @var list<Request> */
    protected array $sentRequests = [];

    /**
     * @param list<Response> $responses
     */
    protected function buildClient(array $responses): EzpayInvoiceClient
    {
        $this->sentRequests = [];
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->sentRequests));

        $config = new EzpayConfig(
            merchantId: self::MERCHANT_ID,
            hashKey: self::HASH_KEY,
            hashIv: self::HASH_IV,
            environment: Environment::Sandbox,
        );

        $http = new EzpayHttpClient(new Client(['handler' => $stack, 'base_uri' => $config->environment->baseUrl()]));

        return new EzpayInvoiceClient(
            config: $config,
            http: $http,
            cryptor: new AesCryptor($config->hashKey, $config->hashIv),
            verifier: new SignatureVerifier($config->hashKey, $config->hashIv),
        );
    }

    /**
     * @return array{merchantId: string, postData: array<string,string>}
     */
    protected function decryptLastRequest(): array
    {
        $this->assertNotEmpty($this->sentRequests, '預期應有至少一筆 HTTP 請求');
        /** @var array{request: Request} $last */
        $last = $this->sentRequests[count($this->sentRequests) - 1];
        $request = $last['request'];
        parse_str((string) $request->getBody(), $body);
        $merchantId = (string) ($body['MerchantID_'] ?? '');
        $encrypted = (string) ($body['PostData_'] ?? '');

        $cryptor = new AesCryptor(self::HASH_KEY, self::HASH_IV);
        $plain = $cryptor->decrypt($encrypted);
        parse_str($plain, $decoded);

        /** @var array<string,string> $decoded */
        return ['merchantId' => $merchantId, 'postData' => $decoded];
    }

    protected function getLastRequestUri(): string
    {
        $this->assertNotEmpty($this->sentRequests);
        /** @var array{request: Request} $last */
        $last = $this->sentRequests[count($this->sentRequests) - 1];
        return (string) $last['request']->getUri();
    }

    /**
     * 把 result array 包成藍新外層 envelope 並計算 CheckCode（如有需要）。
     *
     * @param array<string,mixed> $result
     */
    protected function makeEnvelope(string $status, string $message, array $result, bool $signCheckCode = false): string
    {
        if ($signCheckCode) {
            $verifier = new SignatureVerifier(self::HASH_KEY, self::HASH_IV);
            $result['CheckCode'] = $verifier->compute([
                'MerchantID' => self::MERCHANT_ID,
                'MerchantOrderNo' => (string) ($result['MerchantOrderNo'] ?? ''),
                'InvoiceTransNo' => (string) ($result['InvoiceTransNo'] ?? ''),
                'TotalAmt' => (string) ($result['TotalAmt'] ?? ''),
                'RandomNum' => (string) ($result['RandomNum'] ?? ''),
            ]);
        }

        return json_encode([
            'Status' => $status,
            'Message' => $message,
            'Result' => json_encode($result, JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);
    }
}
