<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class EncryptionUtility
{
    protected const ENCRYPT_METHOD = 'AES-256-CBC';
    protected string $encryptionKey;

    public function __construct()
    {
        $this->encryptionKey = $this->createKey();
    }

    protected function createKey(): string
    {
        if ($encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? null) {
            return hash('sha256', $encryptionKey . '¯\_(ツ)_/¯');
        }

        throw new \Exception('No encryptionKey given.', 1667886790);
    }

    protected function getInitializationVector(): string
    {
        $length = openssl_cipher_iv_length(self::ENCRYPT_METHOD);

        return substr($this->encryptionKey, 0, $length);
    }

    public function encryptString(string $string): ?string
    {
        return base64_encode(openssl_encrypt($string, self::ENCRYPT_METHOD, $this->encryptionKey, 0, $this->getInitializationVector())) ?: null;
    }

    public function encryptArray(array $array): ?string
    {
        $json = json_encode($array);

        return $this->encryptString($json);
    }

    public function decryptString(string $string): ?string
    {
        return openssl_decrypt(base64_decode($string, true), self::ENCRYPT_METHOD, $this->encryptionKey, 0, $this->getInitializationVector()) ?: null;
    }

    public function decryptArray(string $string): ?array
    {
        $json = $this->decryptString($string);

        return json_decode($json, true);
    }

    public static function encryptConfig(array $config): ?string
    {
        return GeneralUtility::makeInstance(self::class)->encryptArray($config);
    }

    public static function decryptConfig(string $string): ?array
    {
        return GeneralUtility::makeInstance(self::class)->decryptArray($string);
    }
}
