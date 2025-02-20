<?php

namespace App\Utils;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DataEncryption {
    private const CIPHER = "aes-256-cbc";
    private const IV_LENGTH = 16;  // 128 bits

    public function __construct(
        protected ParameterBagInterface $bag
    )
    {
    }

    private function getEncryptionKey(): string
    {
        // In production, store this securely in environment variables
        // or a secure key management system
        return $this->bag->get('app_encryption_key');
    }

    public function encrypt(string $data): string
    {
        $key = self::getEncryptionKey();

        // Generate a random IV
        $iv = openssl_random_pseudo_bytes(self::IV_LENGTH);

        // Encrypt the data
        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Combine IV and encrypted data
        $combined = $iv . $encrypted;

        // Convert to base64 for safe storage
        return base64_encode($combined);
    }

    public function decrypt(string $encryptedData): string
    {
        $key = self::getEncryptionKey();

        // Decode from base64
        $combined = base64_decode($encryptedData);

        // Extract IV and encrypted data
        $iv = substr($combined, 0, self::IV_LENGTH);
        $encrypted = substr($combined, self::IV_LENGTH);

        // Decrypt the data
        return openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
