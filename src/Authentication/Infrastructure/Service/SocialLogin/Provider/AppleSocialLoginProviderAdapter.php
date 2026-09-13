<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service\SocialLogin\Provider;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Src\Authentication\Infrastructure\Service\SocialLogin\SocialLoginTemporaryInfoSupport;

final readonly class AppleSocialLoginProviderAdapter implements SocialLoginProviderAdapterInterface
{
    private const AUTHORIZATION_URL = 'https://appleid.apple.com/auth/authorize';

    private const TOKEN_URL = 'https://appleid.apple.com/auth/token';

    private const JWKS_URL = 'https://appleid.apple.com/auth/keys';

    private const ISSUER = 'https://appleid.apple.com';

    public function __construct(private OidcIdTokenVerifier $tokenVerifier) {}

    public function generate(SocialLoginTemporaryInfoSupport $temporaryInfo): string
    {
        return self::AUTHORIZATION_URL.'?'.http_build_query([
            'client_id' => $this->credential('client_id'),
            'redirect_uri' => $this->credential('redirect_uri'),
            'response_type' => 'code',
            'response_mode' => 'query',
            'scope' => 'name email',
            'state' => $temporaryInfo->state(),
            'nonce' => $temporaryInfo->nonce(),
            'code_challenge' => $this->codeChallenge($temporaryInfo->codeVerifier()),
            'code_challenge_method' => 'S256',
        ]);
    }

    public function retrieveProfile(string $authorizationCode, SocialLoginTemporaryInfoSupport $temporaryInfo): ?array
    {
        $response = Http::asForm()->acceptJson()->timeout(10)->post(self::TOKEN_URL, [
            'client_id' => $this->credential('client_id'),
            'client_secret' => $this->clientSecret(),
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->credential('redirect_uri'),
            'code_verifier' => $temporaryInfo->codeVerifier(),
        ]);
        if (! $response->successful()) {
            return in_array($response->json('error'), ['invalid_grant', 'access_denied'], true)
                ? null
                : throw new RuntimeException('Apple OAuthの認可コード交換に失敗しました。');
        }

        $idToken = $response->json('id_token');
        if (! is_string($idToken) || $idToken === '') {
            throw new RuntimeException('Apple OAuthのIDトークンが不正です。');
        }

        $claims = $this->tokenVerifier->verify($idToken, self::JWKS_URL);
        if (! $this->hasValidClaims($claims, $temporaryInfo->nonce())) {
            return null;
        }

        return [
            'provider_user_identifier' => $claims['sub'],
            'email_address' => $claims['email'],
        ];
    }

    private function credential(string $name): string
    {
        $value = config('services.social_login.apple.'.$name);
        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException('Apple OAuthの設定が不足しています。');
        }

        return $value;
    }

    private function clientSecret(): string
    {
        $privateKey = str_replace('\n', "\n", $this->credential('private_key'));
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new RuntimeException('Apple OAuthの秘密鍵が不正です。');
        }

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'ES256',
            'kid' => $this->credential('key_id'),
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $this->credential('team_id'),
            'iat' => time(),
            'exp' => time() + 300,
            'aud' => self::ISSUER,
            'sub' => $this->credential('client_id'),
        ], JSON_THROW_ON_ERROR));
        $signingInput = $header.'.'.$claims;
        if (! openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Apple OAuthのクライアントシークレットを生成できませんでした。');
        }

        return $signingInput.'.'.$this->base64UrlEncode($this->derSignatureToJose($signature));
    }

    private function derSignatureToJose(string $der): string
    {
        $offset = 0;
        if (($der[$offset++] ?? null) !== "\x30") {
            throw new RuntimeException('Apple OAuthの署名形式が不正です。');
        }
        $this->readDerLength($der, $offset);
        if (($der[$offset++] ?? null) !== "\x02") {
            throw new RuntimeException('Apple OAuthの署名形式が不正です。');
        }
        $r = substr($der, $offset, $this->readDerLength($der, $offset));
        $offset += strlen($r);
        if (($der[$offset++] ?? null) !== "\x02") {
            throw new RuntimeException('Apple OAuthの署名形式が不正です。');
        }
        $s = substr($der, $offset, $this->readDerLength($der, $offset));

        $r = str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);
        if (strlen($r) !== 32 || strlen($s) !== 32) {
            throw new RuntimeException('Apple OAuthの署名形式が不正です。');
        }

        return $r.$s;
    }

    private function readDerLength(string $der, int &$offset): int
    {
        $length = ord($der[$offset++] ?? "\0");
        if (($length & 0x80) === 0) {
            return $length;
        }

        $bytes = $length & 0x7F;
        if ($bytes === 0 || $bytes > 4 || strlen($der) < $offset + $bytes) {
            throw new RuntimeException('Apple OAuthの署名形式が不正です。');
        }
        $length = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $length = ($length << 8) | ord($der[$offset++]);
        }

        return $length;
    }

    private function codeChallenge(string $verifier): string
    {
        return $this->base64UrlEncode(hash('sha256', $verifier, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @param array<mixed>|null $claims */
    private function hasValidClaims(?array $claims, string $nonce): bool
    {
        if ($claims === null) {
            return false;
        }
        $emailVerified = $claims['email_verified'] ?? null;

        return ($claims['iss'] ?? null) === self::ISSUER
            && $this->audienceMatches($claims['aud'] ?? null)
            && is_int($claims['exp'] ?? null) && $claims['exp'] > time()
            && is_int($claims['iat'] ?? null) && $claims['iat'] <= time() + 60
            && is_string($claims['sub'] ?? null) && $claims['sub'] !== '' && strlen($claims['sub']) <= 255
            && is_string($claims['nonce'] ?? null) && hash_equals($nonce, $claims['nonce'])
            && is_string($claims['email'] ?? null) && filter_var($claims['email'], FILTER_VALIDATE_EMAIL) !== false
            && ($emailVerified === true || $emailVerified === 'true');
    }

    private function audienceMatches(mixed $audience): bool
    {
        $clientId = $this->credential('client_id');

        return is_string($audience)
            ? hash_equals($clientId, $audience)
            : is_array($audience) && in_array($clientId, $audience, true);
    }
}
