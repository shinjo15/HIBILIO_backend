<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service\SocialLogin\Provider;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Src\Authentication\Infrastructure\Service\SocialLogin\SocialLoginTemporaryInfoSupport;

final readonly class GoogleSocialLoginProviderAdapter implements SocialLoginProviderAdapterInterface
{
    private const AUTHORIZATION_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    public function __construct(private OidcIdTokenVerifier $tokenVerifier) {}

    public function generate(SocialLoginTemporaryInfoSupport $temporaryInfo): string
    {
        return self::AUTHORIZATION_URL.'?'.http_build_query([
            'client_id' => $this->credential('client_id'),
            'redirect_uri' => $this->credential('redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email',
            'state' => $temporaryInfo->state(),
            'nonce' => $temporaryInfo->nonce(),
            'code_challenge' => $this->codeChallenge($temporaryInfo->codeVerifier()),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ]);
    }

    public function retrieveProfile(string $authorizationCode, SocialLoginTemporaryInfoSupport $temporaryInfo): ?array
    {
        $response = Http::asForm()->acceptJson()->timeout(10)->post(self::TOKEN_URL, [
            'code' => $authorizationCode,
            'client_id' => $this->credential('client_id'),
            'client_secret' => $this->credential('client_secret'),
            'redirect_uri' => $this->credential('redirect_uri'),
            'grant_type' => 'authorization_code',
            'code_verifier' => $temporaryInfo->codeVerifier(),
        ]);
        if (! $response->successful()) {
            return in_array($response->json('error'), ['invalid_grant', 'access_denied'], true) ? null : throw new RuntimeException('Google OAuthの認可コード交換に失敗しました。');
        }
        $idToken = $response->json('id_token');
        if (! is_string($idToken) || $idToken === '') {
            throw new RuntimeException('Google OAuthのIDトークンが不正です。');
        }
        $claims = $this->tokenVerifier->verify($idToken, self::JWKS_URL);
        if (! $this->hasValidClaims($claims, $temporaryInfo->nonce())) {
            return null;
        }

        return ['provider_user_identifier' => $claims['sub'], 'email_address' => $claims['email']];
    }

    private function credential(string $name): string
    {
        $value = config('services.social_login.google.'.$name);
        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException('Google OAuthの設定が不足しています。');
        }

        return $value;
    }

    private function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /** @param array<mixed>|null $claims */
    private function hasValidClaims(?array $claims, string $nonce): bool
    {
        if ($claims === null) {
            return false;
        }
        $audience = $claims['aud'] ?? null;

        return in_array($claims['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)
            && (is_string($audience) ? hash_equals($this->credential('client_id'), $audience) : is_array($audience) && in_array($this->credential('client_id'), $audience, true))
            && is_int($claims['exp'] ?? null) && $claims['exp'] > time()
            && is_int($claims['iat'] ?? null) && $claims['iat'] <= time() + 60
            && is_string($claims['sub'] ?? null) && $claims['sub'] !== '' && strlen($claims['sub']) <= 255
            && is_string($claims['nonce'] ?? null) && hash_equals($nonce, $claims['nonce'])
            && is_string($claims['email'] ?? null) && filter_var($claims['email'], FILTER_VALIDATE_EMAIL) !== false
            && ($claims['email_verified'] ?? null) === true;
    }
}
