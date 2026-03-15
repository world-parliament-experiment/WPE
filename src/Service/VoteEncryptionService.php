<?php

namespace App\Service;

use Exception;

class VoteEncryptionService
{
    private $secret;
    private $method = 'aes-256-cbc';

    public function __construct(string $appSecret)
    {
        $this->secret = hash('sha256', $appSecret);
    }

    public function encrypt(int $value): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        $encrypted = openssl_encrypt((string)$value, $this->method, $this->secret, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decrypt(string $encryptedValue): int
    {
        if (is_numeric($encryptedValue)) {
            return (int) $encryptedValue; // Fallback for unencrypted data during migration or old data
        }
        
        $decoded = base64_decode($encryptedValue);
        if (strpos($decoded, '::') === false) {
            // Might be old base64 encoded number or something else, return 0 or throw exception
            return 0;
        }

        list($encrypted_data, $iv) = explode('::', $decoded, 2);
        $decrypted = openssl_decrypt($encrypted_data, $this->method, $this->secret, 0, $iv);
        
        if ($decrypted === false) {
            throw new Exception("Could not decrypt vote value.");
        }
        
        return (int) $decrypted;
    }
}
