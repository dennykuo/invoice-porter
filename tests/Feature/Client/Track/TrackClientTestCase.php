<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Feature\Client\Track;

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
use InvoicePorter\Ezpay\EzpayTrackClient;
use InvoicePorter\Ezpay\Http\EzpayHttpClient;
use PHPUnit\Framework\TestCase;

abstract class TrackClientTestCase extends TestCase
{
    protected const HASH_KEY = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    protected const HASH_IV = 'bbbbbbbbbbbbbbbb';
    protected const MERCHANT_ID = '00000000';

    // 公司層級憑證（用於字軌 API），與商店層級獨立。
    protected const COMPANY_HASH_KEY = 'cccccccccccccccccccccccccccccccc';
    protected const COMPANY_HASH_IV = 'dddddddddddddddd';
    protected const COMPANY_ID = 'C-12345';

    /** @var list<Request> */
    protected array $sentRequests = [];

    /**
     * @param list<Response> $responses
     */
    protected function buildClient(array $responses): EzpayTrackClient
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
            companyId: self::COMPANY_ID,
            companyHashKey: self::COMPANY_HASH_KEY,
            companyHashIv: self::COMPANY_HASH_IV,
        );

        $http = new EzpayHttpClient(new Client(['handler' => $stack, 'base_uri' => $config->environment->baseUrl()]));

        return new EzpayTrackClient(
            config: $config,
            http: $http,
            cryptor: new AesCryptor(self::COMPANY_HASH_KEY, self::COMPANY_HASH_IV),
            verifier: new SignatureVerifier(self::COMPANY_HASH_KEY, self::COMPANY_HASH_IV),
        );
    }

    /**
     * @return array{companyId: string, postData: array<string,string>}
     */
    protected function decryptLastRequest(): array
    {
        $this->assertNotEmpty($this->sentRequests, '預期應有至少一筆 HTTP 請求');
        /** @var array{request: Request} $last */
        $last = $this->sentRequests[count($this->sentRequests) - 1];
        $request = $last['request'];
        parse_str((string) $request->getBody(), $body);
        $companyId = (string) ($body['CompanyID_'] ?? '');
        $encrypted = (string) ($body['PostData_'] ?? '');

        $cryptor = new AesCryptor(self::COMPANY_HASH_KEY, self::COMPANY_HASH_IV);
        $plain = $cryptor->decrypt($encrypted);
        parse_str($plain, $decoded);

        /** @var array<string,string> $decoded */
        return ['companyId' => $companyId, 'postData' => $decoded];
    }

    protected function getLastRequestUri(): string
    {
        $this->assertNotEmpty($this->sentRequests);
        /** @var array{request: Request} $last */
        $last = $this->sentRequests[count($this->sentRequests) - 1];
        return (string) $last['request']->getUri();
    }

    /**
     * 將 result array 包成藍新外層 envelope，需要時自動補 CheckCode（用字軌 5 欄）。
     *
     * @param array<string,mixed> $result
     */
    protected function makeEnvelope(string $status, string $message, array $result, bool $signCheckCode = false): string
    {
        if ($signCheckCode) {
            $verifier = new SignatureVerifier(self::COMPANY_HASH_KEY, self::COMPANY_HASH_IV);
            $result['CheckCode'] = $verifier->compute([
                'CompanyId' => self::COMPANY_ID,
                'AphabeticLetter' => (string) ($result['AphabeticLetter'] ?? ''),
                'StartNumber' => (string) ($result['StartNumber'] ?? ''),
                'EndNumber' => (string) ($result['EndNumber'] ?? ''),
                'ManagementNo' => (string) ($result['ManagementNo'] ?? ''),
            ]);
        }

        return json_encode([
            'Status' => $status,
            'Message' => $message,
            'Result' => json_encode($result, JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR);
    }
}
