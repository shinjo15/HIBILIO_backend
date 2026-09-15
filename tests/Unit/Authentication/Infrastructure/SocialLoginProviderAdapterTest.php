<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Infrastructure;

use Illuminate\Support\Facades\Http;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Authentication\Infrastructure\Service\SocialLogin\Provider\AppleSocialLoginProviderAdapter;
use Src\Authentication\Infrastructure\Service\SocialLogin\Provider\GoogleSocialLoginProviderAdapter;
use Src\Authentication\Infrastructure\Service\SocialLogin\Provider\OidcIdTokenVerifier;
use Src\Authentication\Infrastructure\Service\SocialLogin\SocialLoginTemporaryInfoSupport;
use Tests\TestCase;

final class SocialLoginProviderAdapterTest extends TestCase
{
    public function test_google_authorization_url_contains_state_nonce_and_s256_pkce(): void
    {
        config(['services.social_login.google' => [
            'client_id' => 'google-client',
            'client_secret' => 'google-secret',
            'redirect_uri' => 'https://api.example/callback',
        ]]);
        $temporaryInfo = $this->temporaryInfo(SocialLoginProvider::GOOGLE);

        $url = (new GoogleSocialLoginProviderAdapter(new OidcIdTokenVerifier))->generate($temporaryInfo);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('code', $query['response_type']);
        self::assertSame('state', $query['state']);
        self::assertSame('nonce', $query['nonce']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertSame(
            rtrim(strtr(base64_encode(hash('sha256', 'verifier', true)), '+/', '-_'), '='),
            $query['code_challenge'],
        );
    }

    public function test_google_requires_a_valid_signed_oidc_token_and_verified_email(): void
    {
        [$privateKey, $jwk] = $this->rsaKey();
        config(['services.social_login.google' => [
            'client_id' => 'google-client',
            'client_secret' => 'google-secret',
            'redirect_uri' => 'https://api.example/auth/social/google/callback',
        ]]);
        $temporaryInfo = $this->temporaryInfo(SocialLoginProvider::GOOGLE);
        $token = $this->rsaJwt($privateKey, [
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-client',
            'iat' => time(),
            'exp' => time() + 600,
            'sub' => 'google-sub',
            'nonce' => 'nonce',
            'email' => 'verified@example.com',
            'email_verified' => true,
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['id_token' => $token]),
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response(['keys' => [$jwk]]),
        ]);

        $profile = (new GoogleSocialLoginProviderAdapter(new OidcIdTokenVerifier))
            ->retrieveProfile('code', $temporaryInfo);

        self::assertSame([
            'provider_user_identifier' => 'google-sub',
            'email_address' => 'verified@example.com',
        ], $profile);
    }

    public function test_google_rejects_a_token_with_a_different_nonce(): void
    {
        [$privateKey, $jwk] = $this->rsaKey();
        config(['services.social_login.google' => [
            'client_id' => 'google-client',
            'client_secret' => 'google-secret',
            'redirect_uri' => 'https://api.example/callback',
        ]]);
        $token = $this->rsaJwt($privateKey, [
            'iss' => 'https://accounts.google.com',
            'aud' => 'google-client',
            'iat' => time(),
            'exp' => time() + 600,
            'sub' => 'google-sub',
            'nonce' => 'wrong',
            'email' => 'verified@example.com',
            'email_verified' => true,
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['id_token' => $token]),
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response(['keys' => [$jwk]]),
        ]);

        self::assertNull((new GoogleSocialLoginProviderAdapter(new OidcIdTokenVerifier))
            ->retrieveProfile('code', $this->temporaryInfo(SocialLoginProvider::GOOGLE)));
    }

    public function test_apple_uses_an_es256_client_secret_and_validates_its_oidc_identity(): void
    {
        [$tokenKey, $jwk] = $this->rsaKey();
        $clientKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        self::assertNotFalse($clientKey);
        self::assertTrue(openssl_pkey_export($clientKey, $privateKey));
        config(['services.social_login.apple' => [
            'client_id' => 'com.example.app',
            'team_id' => 'team-id',
            'key_id' => 'key-id',
            'private_key' => $privateKey,
            'redirect_uri' => 'https://api.example/auth/social/apple/callback',
        ]]);
        $token = $this->rsaJwt($tokenKey, [
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.example.app',
            'iat' => time(),
            'exp' => time() + 600,
            'sub' => 'apple-sub',
            'nonce' => 'nonce',
            'email' => 'private@example.com',
            'email_verified' => 'true',
        ]);
        Http::fake([
            'https://appleid.apple.com/auth/token' => function ($request) use ($token) {
                $clientSecret = $request->data()['client_secret'] ?? '';
                self::assertIsString($clientSecret);
                self::assertSame('ES256', $this->jwtPart($clientSecret, 0)['alg'] ?? null);
                self::assertSame('team-id', $this->jwtPart($clientSecret, 1)['iss'] ?? null);

                return Http::response(['id_token' => $token]);
            },
            'https://appleid.apple.com/auth/keys' => Http::response(['keys' => [$jwk]]),
        ]);

        $profile = (new AppleSocialLoginProviderAdapter(new OidcIdTokenVerifier))
            ->retrieveProfile('code', $this->temporaryInfo(SocialLoginProvider::APPLE));

        self::assertSame([
            'provider_user_identifier' => 'apple-sub',
            'email_address' => 'private@example.com',
        ], $profile);
    }

    /** @return array{0: \OpenSSLAsymmetricKey, 1: array{kid: string, kty: string, alg: string, n: string, e: string}} */
    private function rsaKey(): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($key);
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        return [$key, [
            'kid' => 'test-key',
            'kty' => 'RSA',
            'alg' => 'RS256',
            'n' => $this->base64Url($details['rsa']['n']),
            'e' => $this->base64Url($details['rsa']['e']),
        ]];
    }

    /** @param array<string, mixed> $claims */
    private function rsaJwt(\OpenSSLAsymmetricKey $key, array $claims): string
    {
        $header = $this->base64Url(json_encode([
            'alg' => 'RS256',
            'kid' => 'test-key',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->base64Url(json_encode($claims, JSON_THROW_ON_ERROR));
        self::assertTrue(openssl_sign("$header.$payload", $signature, $key, OPENSSL_ALGO_SHA256));

        return "$header.$payload.".$this->base64Url($signature);
    }

    private function temporaryInfo(SocialLoginProvider $provider): SocialLoginTemporaryInfoSupport
    {
        return new SocialLoginTemporaryInfoSupport(
            'state',
            $provider,
            hash('sha256', 'browser'),
            'verifier',
            'nonce',
        );
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @return array<string, mixed> */
    private function jwtPart(string $token, int $part): array
    {
        $encoded = explode('.', $token)[$part] ?? '';
        $padding = (4 - strlen($encoded) % 4) % 4;
        $decoded = base64_decode(strtr($encoded, '-_', '+/').str_repeat('=', $padding), true);
        $json = is_string($decoded) ? json_decode($decoded, true) : null;

        return is_array($json) ? $json : [];
    }
}
