<?php

namespace Source\Project\LogicManagers;

use Source\Base\Constants\Settings\Path;
use Source\Base\Core\LogicManager;

class EncryptLM extends LogicManager
{
    protected static string $ENCRYPTION_KEY = '';

    // Encrypt Function

    protected static function getKey(): void
    {
        if (!static::$ENCRYPTION_KEY) {
            static::$ENCRYPTION_KEY = file_get_contents(Path::RESOURCES_DIR . 'files/key.txt');
        }
    }
    /**
     * @param $text
     * @return string
     */
    static function encrypt($text): string
    {
        static::getKey();

        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);

        $ciphertext_raw = openssl_encrypt($text, $cipher, static::$ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, static::$ENCRYPTION_KEY, $as_binary = true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);

        return $ciphertext;
    }

    // Decrypt Function

    /**
     * @param $decrypt
     * @return string|null
     */
    static function decrypt($decrypt): string|null
    {
        static::getKey();

        $c = base64_decode($decrypt);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");

        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);

        $plaintext = openssl_decrypt($ciphertext_raw, $cipher, static::$ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, static::$ENCRYPTION_KEY, $as_binary = true);

        if (hash_equals($hmac, $calcmac)) {
            return $plaintext ?: null;
        }

        return null;
    }
}