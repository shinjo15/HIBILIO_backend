<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service\SocialLogin\Provider;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OidcIdTokenVerifier
{
    private const TIMEOUT_SECONDS = 10;

    /** @return null|array<mixed> */
    public function verify(string $token, string $jwksUrl): ?array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedClaims, $encodedSignature] = $segments;
        $header = $this->decodeJsonSegment($encodedHeader);
        $claims = $this->decodeJsonSegment($encodedClaims);
        $signature = $this->decodeBase64Url($encodedSignature);
        if ($header === null || $claims === null || $signature === null) {
            return null;
        }

        $kid = $header['kid'] ?? null;
        if (($header['alg'] ?? null) !== 'RS256' || ! is_string($kid) || $kid === '') {
            return null;
        }

        $response = Http::acceptJson()->timeout(self::TIMEOUT_SECONDS)->get($jwksUrl);
        if (! $response->successful()) {
            throw new RuntimeException('OpenID Connectの署名鍵を取得できませんでした。');
        }
        $key = $this->keyFromJwks($response, $kid);
        if ($key === null) {
            return null;
        }

        $result = openssl_verify($encodedHeader.'.'.$encodedClaims, $signature, $key, OPENSSL_ALGO_SHA256);
        if ($result === 0) {
            return null;
        }
        if ($result !== 1) {
            throw new RuntimeException('OpenID Connect IDトークンの署名を検証できませんでした。');
        }

        return $claims;
    }

    private function keyFromJwks(Response $response, string $kid): ?string
    {
        $keys = $response->json('keys');
        if (! is_array($keys)) {
            throw new RuntimeException('OpenID Connectの署名鍵レスポンスが不正です。');
        }

        foreach ($keys as $jwk) {
            if (! is_array($jwk) || ($jwk['kid'] ?? null) !== $kid || ($jwk['kty'] ?? null) !== 'RSA') {
                continue;
            }
            $certificates = $jwk['x5c'] ?? null;
            if (is_array($certificates) && is_string($certificates[0] ?? null)) {
                return "-----BEGIN CERTIFICATE-----\n".chunk_split($certificates[0], 64, "\n")."-----END CERTIFICATE-----\n";
            }
            if (is_string($jwk['n'] ?? null) && is_string($jwk['e'] ?? null)) {
                return $this->rsaPublicKey($jwk['n'], $jwk['e']);
            }
        }

        return null;
    }

    private function rsaPublicKey(string $modulus, string $exponent): string
    {
        $modulus = $this->decodeBase64Url($modulus);
        $exponent = $this->decodeBase64Url($exponent);
        if ($modulus === null || $exponent === null) {
            throw new RuntimeException('OpenID Connectの署名鍵が不正です。');
        }

        $der = $this->sequence($this->integer($modulus).$this->integer($exponent));

        $algorithm = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $subjectPublicKey = "\x03".$this->length(strlen($der) + 1)."\x00".$der;

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($this->sequence(
            $algorithm.$subjectPublicKey,
        )), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private function integer(string $value): string
    {
        if ($value === '' || (ord($value[0]) & 0x80) !== 0) {
            $value = "\x00".$value;
        }

        return "\x02".$this->length(strlen($value)).$value;
    }

    private function sequence(string $value): string
    {
        return "\x30".$this->length(strlen($value)).$value;
    }

    private function length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }
        $encoded = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($encoded)).$encoded;
    }

    /** @return null|array<mixed> */
    private function decodeJsonSegment(string $segment): ?array
    {
        $decoded = $this->decodeBase64Url($segment);
        if ($decoded === null) {
            return null;
        }
        $json = json_decode($decoded, true);

        return is_array($json) ? $json : null;
    }

    private function decodeBase64Url(string $value): ?string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', $padding), true);

        return $decoded === false ? null : $decoded;
    }
}
