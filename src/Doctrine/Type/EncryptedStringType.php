<?php

namespace App\Doctrine\Type;

use App\Service\UserEncryptionService;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\StringType;

class EncryptedStringType extends StringType
{
    public const NAME = 'encrypted_string';

    private static ?UserEncryptionService $encryptionService = null;

    /**
     * We use a static setter to inject the service because Doctrine types
     * are instantiated globally by DBAL, making normal DI impossible.
     */
    public static function setEncryptionService(UserEncryptionService $encryptionService): void
    {
        self::$encryptionService = $encryptionService;
    }

    private function getEncryptionService(): UserEncryptionService
    {
        if (self::$encryptionService === null) {
            $secret = $_ENV['APP_SECRET'] ?? $_SERVER['APP_SECRET'] ?? 'local';
            self::$encryptionService = new UserEncryptionService($secret);
        }
        return self::$encryptionService;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $this->getEncryptionService()->encrypt((string) $value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $this->getEncryptionService()->decrypt((string) $value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
