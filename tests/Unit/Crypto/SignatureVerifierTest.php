<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Crypto;

use InvoicePorter\Ezpay\Crypto\SignatureVerifier;
use InvoicePorter\Ezpay\Exceptions\EzpayCheckCodeException;
use PHPUnit\Framework\TestCase;

final class SignatureVerifierTest extends TestCase
{
    private const HASH_KEY = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const HASH_IV = 'bbbbbbbbbbbbbbbb';

    /**
     * 依藍新文件附件二之邏輯：將五欄做 ksort、http_build_query 後夾上 HashIV / HashKey 取 SHA256 大寫。
     * 此測試以同一份實作產出 golden value，再做正向 / 反向驗證。
     */
    public function testComputeFollowsDocumentedAlgorithm(): void
    {
        $verifier = new SignatureVerifier(self::HASH_KEY, self::HASH_IV);

        $fields = [
            'MerchantID' => '00000000',
            'MerchantOrderNo' => 'ORD20260101',
            'InvoiceTransNo' => '20051309002377869',
            'TotalAmt' => '500',
            'RandomNum' => '0991',
        ];

        $expected = strtoupper(hash(
            'sha256',
            'HashIV=' . self::HASH_IV
            . '&InvoiceTransNo=20051309002377869'
            . '&MerchantID=00000000'
            . '&MerchantOrderNo=ORD20260101'
            . '&RandomNum=0991'
            . '&TotalAmt=500'
            . '&HashKey=' . self::HASH_KEY,
        ));

        $this->assertSame($expected, $verifier->compute($fields));
    }

    public function testVerifyAcceptsValidCheckCode(): void
    {
        $verifier = new SignatureVerifier(self::HASH_KEY, self::HASH_IV);
        $fields = [
            'MerchantID' => '00000000',
            'MerchantOrderNo' => 'ORD20260101',
            'InvoiceTransNo' => '20051309002377869',
            'TotalAmt' => '500',
            'RandomNum' => '0991',
        ];

        $verifier->verify($fields, $verifier->compute($fields));

        $this->expectNotToPerformAssertions();
    }

    public function testVerifyRejectsTamperedCheckCode(): void
    {
        $verifier = new SignatureVerifier(self::HASH_KEY, self::HASH_IV);
        $fields = [
            'MerchantID' => '00000000',
            'MerchantOrderNo' => 'ORD20260101',
            'InvoiceTransNo' => '20051309002377869',
            'TotalAmt' => '500',
            'RandomNum' => '0991',
        ];

        $this->expectException(EzpayCheckCodeException::class);
        $verifier->verify($fields, str_repeat('A', 64));
    }

    public function testFieldOrderDoesNotMatter(): void
    {
        $verifier = new SignatureVerifier(self::HASH_KEY, self::HASH_IV);

        $a = $verifier->compute([
            'TotalAmt' => '500',
            'MerchantID' => '00000000',
            'InvoiceTransNo' => '20051309002377869',
            'RandomNum' => '0991',
            'MerchantOrderNo' => 'ORD20260101',
        ]);

        $b = $verifier->compute([
            'MerchantID' => '00000000',
            'MerchantOrderNo' => 'ORD20260101',
            'InvoiceTransNo' => '20051309002377869',
            'TotalAmt' => '500',
            'RandomNum' => '0991',
        ]);

        $this->assertSame($a, $b);
    }
}
