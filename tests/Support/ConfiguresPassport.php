<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;

trait ConfiguresPassport
{
    /** @var array{private: string, public: string}|null */
    private static ?array $passportKeys = null;

    protected function configurePassport(): void
    {
        $keys = self::$passportKeys ??= $this->generatePassportKeys();
        config()->set('app.key', 'base64:MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDA=');
        config()->set('passport.private_key', $keys['private']);
        config()->set('passport.public_key', $keys['public']);
    }

    /** @return array{private: string, public: string} */
    private function generatePassportKeys(): array
    {
        $key = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        Assert::assertNotFalse($key);
        Assert::assertTrue(openssl_pkey_export($key, $privateKey));
        $details = openssl_pkey_get_details($key);
        Assert::assertIsArray($details);

        return ['private' => $privateKey, 'public' => $details['key']];
    }
}
