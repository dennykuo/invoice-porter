<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Crypto;

use InvoicePorter\Ezpay\Exceptions\EzpayTransportException;

final class AesCryptor
{
    private const CIPHER = 'AES-256-CBC';
    private const BLOCK_SIZE = 32;

    public function __construct(
        private readonly string $hashKey,
        private readonly string $hashIv,
    ) {
    }

    public function encrypt(string $plain): string
    {
        $padded = $this->pkcs7Pad($plain, self::BLOCK_SIZE);

        $cipher = openssl_encrypt(
            $padded,
            self::CIPHER,
            $this->hashKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $this->hashIv,
        );

        if ($cipher === false) {
            throw new EzpayTransportException('AES encryption failed: ' . openssl_error_string());
        }

        return trim(bin2hex($cipher));
    }

    public function decrypt(string $hex): string
    {
        if ($hex === '' || strlen($hex) % 2 !== 0 || !ctype_xdigit($hex)) {
            throw new EzpayTransportException('AES payload is not valid hex');
        }

        $binary = hex2bin($hex);
        if ($binary === false) {
            throw new EzpayTransportException('AES payload is not valid hex');
        }

        $plain = openssl_decrypt(
            $binary,
            self::CIPHER,
            $this->hashKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $this->hashIv,
        );

        if ($plain === false) {
            throw new EzpayTransportException('AES decryption failed: ' . openssl_error_string());
        }

        return $this->pkcs7Unpad($plain);
    }

    private function pkcs7Pad(string $string, int $blockSize): string
    {
        $len = strlen($string);
        $pad = $blockSize - ($len % $blockSize);

        return $string . str_repeat(chr($pad), $pad);
    }

    private function pkcs7Unpad(string $string): string
    {
        $len = strlen($string);
        if ($len === 0) {
            return $string;
        }

        $pad = ord($string[$len - 1]);
        if ($pad < 1 || $pad > self::BLOCK_SIZE) {
            return $string;
        }

        return substr($string, 0, $len - $pad);
    }
}
