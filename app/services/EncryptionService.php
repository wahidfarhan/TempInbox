<?php

namespace App\Services;

/**
 * Encryption Service
 * 
 * Handles encryption and decryption of sensitive credentials
 * using AES-256-GCM (authenticated encryption)
 */
class EncryptionService
{
    private static string $method = 'aes-256-gcm';
    
    /**
     * Get or generate an encryption key
     * In production, this should be stored securely outside the app
     */
    private static function getKey(): string
    {
        // Use application URL + a fixed salt for key derivation
        $config = require ROOT_DIR . '/config/config.php';
        $baseUrl = $config['app']['url'] ?? '';
        
        // Derive a key from the URL using PBKDF2
        $salt = 'TempInbox_2026'; // Fixed salt - in production, use env variable
        $key = hash_pbkdf2('sha256', $baseUrl . $salt, $salt, 100000, 32, true);
        
        return $key;
    }
    
    /**
     * Encrypt a sensitive value
     */
    public static function encrypt(string $plaintext): string
    {
        $key = self::getKey();
        $iv = openssl_random_pseudo_bytes(12); // 12 bytes for GCM
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $plaintext,
            self::$method,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            throw new \Exception("Encryption failed: " . openssl_error_string());
        }
        
        // Return base64 encoded: IV + TAG + CIPHERTEXT
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Decrypt a sensitive value
     */
    public static function decrypt(string $ciphertext): string
    {
        $key = self::getKey();
        
        $data = base64_decode($ciphertext, true);
        if ($data === false) {
            throw new \Exception("Invalid encrypted data format");
        }
        
        $iv = substr($data, 0, 12);     // 12 bytes IV
        $tag = substr($data, 12, 16);   // 16 bytes TAG
        $encrypted = substr($data, 28); // Rest is ciphertext
        
        $plaintext = openssl_decrypt(
            $encrypted,
            self::$method,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($plaintext === false) {
            throw new \Exception("Decryption failed: Invalid tag or corrupted data");
        }
        
        return $plaintext;
    }
}
