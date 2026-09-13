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
    public function test_google_verifies_the_jwks_signature_and_oidc_claims(): void
    {
        [$privateKey, $jwk] = $this->rsaKey();
        config(['services.social_login.google' => [
            'client_id' => 'google-client', 'client_secret' => 'google-secret', 'redirect_uri' => 'https://app.test/callback',
        ]]);
        $temporaryInfo = new SocialLoginTemporaryInfoSupport(
            'state', SocialLoginProvider::GOOGLE, hash('sha256', 'browser'), 'verifier', 'nonce', null,
        );
        $token = $this->jwt($privateKey, [
            'iss' => 'https://accounts.google.com', 'aud' => 'google-client', 'iat' => time(), 'exp' => time() + 600,
            'sub' => 'google-sub', 'nonce' => 'nonce', 'email' => 'verified@example.com', 'email_verified' => true,
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['id_token' => $token], 200),
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response(['keys' => [$jwk]], 200),
        ]);

        $profile = (new GoogleSocialLoginProviderAdapter(new OidcIdTokenVerifier))->retrieveProfile('code', $temporaryInfo);

        self::assertSame(['provider_user_identifier' => 'google-sub', 'email_address' => 'verified@example.com'], $profile);
    }

    public function test_google_rejects_a_token_with_a_different_nonce(): void
    {
        [$privateKey, $jwk] = $this->rsaKey();
        config(['services.social_login.google' => [
            'client_id' => 'google-client', 'client_secret' => 'google-secret', 'redirect_uri' => 'https://app.test/callback',
        ]]);
        $temporaryInfo = new SocialLoginTemporaryInfoSupport(
            'state', SocialLoginProvider::GOOGLE, hash('sha256', 'browser'), 'verifier', 'nonce', null,
        );
        $token = $this->jwt($privateKey, [
            'iss' => 'https://accounts.google.com', 'aud' => 'google-client', 'iat' => time(), 'exp' => time() + 600,
            'sub' => 'google-sub', 'nonce' => 'other-nonce', 'email' => 'verified@example.com', 'email_verified' => true,
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['id_token' => $token], 200),
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response(['keys' => [$jwk]], 200),
        ]);

        self::assertNull((new GoogleSocialLoginProviderAdapter(new OidcIdTokenVerifier))->retrieveProfile('code', $temporaryInfo));
    }

    public function test_apple_verifies_jwks_claims_and_sends_an_es256_client_secret(): void
    {
        [$tokenKey, $jwk] = $this->rsaKey();
        $clientKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        self::assertNotFalse($clientKey);
        self::assertTrue(openssl_pkey_export($clientKey, $privateKey));
        config(['services.social_login.apple' => [
            'client_id' => 'com.example.app', 'team_id' => 'team-id', 'key_id' => 'key-id',
            'private_key' => $privateKey, 'redirect_uri' => 'https://app.test/callback',
        ]]);
        $temporaryInfo = new SocialLoginTemporaryInfoSupport(
            'state', SocialLoginProvider::APPLE, hash('sha256', 'browser'), 'verifier', 'nonce', null,
        );
        $token = $this->jwt($tokenKey, [
            'iss' => 'https://appleid.apple.com', 'aud' => 'com.example.app', 'iat' => time(), 'exp' => time() + 600,
            'sub' => 'apple-sub', 'nonce' => 'nonce', 'email' => 'private@example.com', 'email_verified' => 'true',
        ]);
        Http::fake([
            'https://appleid.apple.com/auth/token' => function ($request) use ($token) {
                $clientSecret = $request->data()['client_secret'] ?? '';
                self::assertIsString($clientSecret);
                self::assertSame('ES256', $this->jwtPart($clientSecret, 0)['alg'] ?? null);

                return Http::response(['id_token' => $token], 200);
            },
            'https://appleid.apple.com/auth/keys' => Http::response(['keys' => [$jwk]], 200),
        ]);

        $profile = (new AppleSocialLoginProviderAdapter(new OidcIdTokenVerifier))->retrieveProfile('code', $temporaryInfo);

        self::assertSame(['provider_user_identifier' => 'apple-sub', 'email_address' => 'private@example.com'], $profile);
    }

    /** @return array{0: \OpenSSLAsymmetricKey, 1: array{kid: string, kty: string, alg: string, n: string, e: string}} */
    private function rsaKey(): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($key);
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        return [$key, [
            'kid' => 'test-key', 'kty' => 'RSA', 'alg' => 'RS256',
            'n' => $this->base64Url($details['rsa']['n']), 'e' => $this->base64Url($details['rsa']['e']),
        ]];
    }

    /** @param array<string, mixed> $claims */
    private function jwt(\OpenSSLAsymmetricKey $key, array $claims): string
    {
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'kid' => 'test-key', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64Url(json_encode($claims, JSON_THROW_ON_ERROR));
        self::assertTrue(openssl_sign("$header.$payload", $signature, $key, OPENSSL_ALGO_SHA256));

        return "$header.$payload.".$this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @return array<string, mixed> */
    private function jwtPart(string $token, int $part): array
    {
        $encoded = explode('.', $token)[$part];
        $padding = (4 - strlen($encoded) % 4) % 4;
        $decoded = base64_decode(strtr($encoded, '-_', '+/').str_repeat('=', $padding), true);
        $json = is_string($decoded) ? json_decode($decoded, true) : null;

        return is_array($json) ? $json : [];
    }
}
