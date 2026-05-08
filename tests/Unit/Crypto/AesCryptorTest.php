<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Crypto;

use InvoicePorter\Ezpay\Crypto\AesCryptor;
use InvoicePorter\Ezpay\Exceptions\EzpayTransportException;
use PHPUnit\Framework\TestCase;

final class AesCryptorTest extends TestCase
{
    private const HASH_KEY = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const HASH_IV = 'bbbbbbbbbbbbbbbb';

    public function testEncryptProducesHexString(): void
    {
        $cryptor = new AesCryptor(self::HASH_KEY, self::HASH_IV);
        $cipher = $cryptor->encrypt('hello world');

        $this->assertNotSame('', $cipher);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $cipher);
        $this->assertSame(0, strlen($cipher) % 64, 'AES-256-CBC + 32-byte block padding 後的 hex 長度應為 64 倍數');
    }

    public function testEncryptIsDeterministicWithFixedKeyAndIv(): void
    {
        $cryptor = new AesCryptor(self::HASH_KEY, self::HASH_IV);

        $first = $cryptor->encrypt('Foo=bar&Baz=qux');
        $second = $cryptor->encrypt('Foo=bar&Baz=qux');

        $this->assertSame($first, $second, 'AES-256-CBC + 同一 IV + 同一 plaintext 應為固定輸出');
    }

    public function testEncryptThenDecryptRoundTrip(): void
    {
        $cryptor = new AesCryptor(self::HASH_KEY, self::HASH_IV);
        $plain = 'MerchantOrderNo=ORD20260101&TotalAmt=500&Status=1';

        $cipher = $cryptor->encrypt($plain);

        $this->assertSame($plain, $cryptor->decrypt($cipher));
    }

    public function testEncryptHandlesUtf8(): void
    {
        $cryptor = new AesCryptor(self::HASH_KEY, self::HASH_IV);
        $plain = 'BuyerName=王大品&ItemName=商品一|商品二';

        $this->assertSame($plain, $cryptor->decrypt($cryptor->encrypt($plain)));
    }

    public function testDecryptInvalidHexThrows(): void
    {
        $cryptor = new AesCryptor(self::HASH_KEY, self::HASH_IV);

        $this->expectException(EzpayTransportException::class);
        $cryptor->decrypt('zz-not-hex');
    }
}
