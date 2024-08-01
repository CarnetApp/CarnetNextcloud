<?php

declare(strict_types=1);

/*
 * This file is part of the nelexa/zip package.
 * (c) Ne-Lexa <https://github.com/Ne-Lexa/php-zip>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpZip\IO\Filter\Cipher\WinZipAes;

use PhpZip\Exception\RuntimeException;
use PhpZip\Exception\ZipAuthenticationException;
use PhpZip\Util\CryptoUtil;

/**
 * WinZip Aes Encryption.
 *
 * @see https://pkware.cachefly.net/webdocs/casestudies/APPNOTE.TXT APPENDIX E
 * @see https://www.winzip.com/win/en/aes_info.html
 *
 * @internal
 */
class WinZipAesContext
{
    /** @var int AES Block size */
    public const BLOCK_SIZE = self::IV_SIZE;

    /** @var int Footer size */
    public const FOOTER_SIZE = 10;

    /** @var int The iteration count for the derived keys of the cipher, KLAC and MAC. */
    public const ITERATION_COUNT = 1000;

    /** @var int Password verifier size */
    public const PASSWORD_VERIFIER_SIZE = 2;

    /** @var int IV size */
    public const IV_SIZE = 16;

    private string $iv;

    private string $key;

    private \HashContext $hmacContext;

    private string $passwordVerifier;

    public function __construct(int $encryptionStrengthBits, string $password, string $salt)
    {
        if ($password === '') {
            throw new RuntimeException('$password is empty');
        }

        if (empty($salt)) {
            throw new RuntimeException('$salt is empty');
        }

        // WinZip 99-character limit https://sourceforge.net/p/p7zip/discussion/383044/thread/c859a2f0/
        $password = substr($password, 0, 99);

        $this->iv = str_repeat("\0", self::IV_SIZE);
        $keyStrengthBytes = (int) ($encryptionStrengthBits / 8);
        $hashLength = $keyStrengthBytes * 2 + self::PASSWORD_VERIFIER_SIZE * 8;

        $hash = hash_pbkdf2(
            'sha1',
            $password,
            $salt,
            self::ITERATION_COUNT,
            $hashLength,
            true
        );

        $this->key = substr($hash, 0, $keyStrengthBytes);
        $sha1Mac = substr($hash, $keyStrengthBytes, $keyStrengthBytes);
        $this->hmacContext = hash_init('sha1', \HASH_HMAC, $sha1Mac);
        $this->passwordVerifier = substr($hash, 2 * $keyStrengthBytes, self::PASSWORD_VERIFIER_SIZE);
    }

    public function getPasswordVerifier(): string
    {
        return $this->passwordVerifier;
    }

    public function updateIv(): void
    {
        for ($ivCharIndex = 0; $ivCharIndex < self::IV_SIZE; $ivCharIndex++) {
            $ivByte = \ord($this->iv[$ivCharIndex]);

            if (++$ivByte === 256) {
                // overflow, set this one to 0, increment next
                $this->iv[$ivCharIndex] = "\0";
            } else {
                // no overflow, just write incremented number back and abort
                $this->iv[$ivCharIndex] = \chr($ivByte);

                break;
            }
        }
    }

    public function decryption(string $data): string
    {
        hash_update($this->hmacContext, $data);

        return CryptoUtil::decryptAesCtr($data, $this->key, $this->iv);
    }

    public function encrypt(string $data): string
    {
        $encryptionData = CryptoUtil::encryptAesCtr($data, $this->key, $this->iv);
        hash_update($this->hmacContext, $encryptionData);

        return $encryptionData;
    }

    /**
     * @throws ZipAuthenticationException
     */
    public function checkAuthCode(string $authCode): void
    {
        $hmac = $this->getHmac();

        // check authenticationCode
        if (strcmp($hmac, $authCode) !== 0) {
            throw new ZipAuthenticationException('Authenticated WinZip AES entry content has been tampered with.');
        }
    }

    public function getHmac(): string
    {
        return substr(
            hash_final($this->hmacContext, true),
            0,
            self::FOOTER_SIZE
        );
    }
}
