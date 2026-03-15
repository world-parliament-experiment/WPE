<?php

namespace App\Service;

use Exception;

class UserEncryptionService
{
    private $secret;
    private $method = 'aes-256-cbc';

    public function __construct(string $userEncryptSecret)
    {
        // Final diagnostic check.
        $length = strlen($userEncryptSecret);
        $firstChar = substr($userEncryptSecret, 0, 1);
        $lastChar = substr($userEncryptSecret, -1);

        throw new \InvalidArgumentException(
            "DIAGNOSTIC: Please check your server's environment variable for USER_ENCRYPT_SECRET. " .
            "The service received a string with Length: $length, First char: '$firstChar', Last char: '$lastChar'. " .
            "Compare this with your actual secret to find the discrepancy (e.g., a typo or special characters being misinterpreted by the server config)."
        );

        if (empty($userEncryptSecret)) {
            throw new \InvalidArgumentException('The USER_ENCRYPT_SECRET environment variable is empty or not loaded correctly in your web server environment. Please check your server configuration (e.g., Apache SetEnv) and ensure the service has been restarted.');
        }
        $this->secret = hash('sha256', $userEncryptSecret);
    }

    /**
     * Deterministic encryption for searchable fields like email or phone.
     * The same input always produces the same encrypted string.
     */
    public function encrypt(string $value): string
    {
        if (empty($value)) {
            return $value;
        }
        
        // Use a static IV derived from the secret so encryption is deterministic
        $iv = substr(hash('sha256', $this->secret . 'deterministic_user_iv'), 0, openssl_cipher_iv_length($this->method));
        $encrypted = openssl_encrypt($value, $this->method, $this->secret, 0, $iv);
        return base64_encode('enc::' . $encrypted);
    }

    /**
     * Decrypts deterministically encrypted strings.
     * Includes a fallback to return the original string if it is not encrypted (for migration).
     */
    public function decrypt(string $encryptedValue): string
    {
        if (empty($encryptedValue)) {
            return $encryptedValue;
        }
        
        $decoded = base64_decode($encryptedValue, true);
        
        // If it's not base64 or doesn't have our prefix, assume it's unencrypted (legacy data fallback)
        if ($decoded === false || strpos($decoded, 'enc::') !== 0) {
            return $encryptedValue;
        }

        $encrypted_data = substr($decoded, 5);
        $iv = substr(hash('sha256', $this->secret . 'deterministic_user_iv'), 0, openssl_cipher_iv_length($this->method));
        $decrypted = openssl_decrypt($encrypted_data, $this->method, $this->secret, 0, $iv);
        
        if ($decrypted === false) {
            throw new Exception("Could not decrypt user data. Key hash used: " . $this->secret);
        }
        
        return $decrypted;
    }
}
