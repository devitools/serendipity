<?php

declare(strict_types=1);

namespace Serendipity\Crypt;

use Constructo\Support\Set;
use InvalidArgumentException;
use RuntimeException;

use function base64_decode;
use function base64_encode;
use function define;
use function defined;
use function openssl_decrypt;
use function openssl_encrypt;
use function random_bytes;
use function Serendipity\Type\Cast\arrayify;
use function Serendipity\Type\Cast\stringify;
use function Serendipity\Type\Json\decode;
use function Serendipity\Type\Json\encode;
use function Serendipity\Type\Util\extractString;
use function sprintf;

if (! defined('DEFAULT_CRYPT_KEY')) {
    define('DEFAULT_CRYPT_KEY', base64_encode(stringify(random_bytes(32))));
}

if (! function_exists(__NAMESPACE__ . '\encrypt')) {
    function encrypt(string $plaintext, string $key = DEFAULT_CRYPT_KEY): string
    {
        $salt = random_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $salt);
        if ($ciphertext === false) {
            throw new RuntimeException(sprintf("Encryption failed: '%s'.", $plaintext));
        }

        $data = group('aes-256-cbc', base64_encode($salt), base64_encode($ciphertext));
        return base64_encode($data);
    }
}

if (! function_exists(__NAMESPACE__ . '\decrypt')) {
    function decrypt(string $encrypted, string $key = DEFAULT_CRYPT_KEY): string
    {
        $set = ungroup($encrypted);
        if ($set->get('algo') !== 'aes-256-cbc') {
            throw new InvalidArgumentException('Unknown encryption algorithm.');
        }

        $salt = base64_decode(stringify($set->get('salt')));
        $ciphertext = base64_decode(stringify($set->get('data')));

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $salt);
        if ($decrypted === false) {
            throw new RuntimeException(sprintf("Decryption failed: '%s'.", $encrypted));
        }
        return $decrypted;
    }
}

if (! function_exists(__NAMESPACE__ . '\group')) {
    function group(string $algo, string $salt, string $ciphertext): string
    {
        $data = [
            'algo' => $algo,
            'salt' => $salt,
            'data' => $ciphertext,
        ];
        $encoded = encode($data);
        if ($encoded === null) {
            throw new RuntimeException(sprintf("Encryption failed: '%s'.", $ciphertext));
        }
        return $encoded;
    }
}

if (! function_exists(__NAMESPACE__ . '\ungroup')) {
    function ungroup(string $encrypted): Set
    {
        $decoded = arrayify(decode(base64_decode($encrypted)));
        $algo = extractString($decoded, 'algo');
        $salt = extractString($decoded, 'salt');
        $data = extractString($decoded, 'data');
        return ($algo && $salt && $data)
            ? new Set([
                'algo' => $algo,
                'salt' => $salt,
                'data' => $data,
            ])
            : throw new InvalidArgumentException(sprintf("Invalid encrypted format: '%s'.", $encrypted));
    }
}
