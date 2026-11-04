<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Infrastructure\Service;

use Aws\S3\Exception\S3Exception;
use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\UnableToCheckFileExistence;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Src\Account\Infrastructure\Service\AccountImageUrlService;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\Support\InteractsWithS3HttpFixture;
use Tests\TestCase;

final class AccountImageUrlServiceTest extends TestCase
{
    use InteractsWithS3HttpFixture;

    #[DataProvider('imageTypes')]
    public function test_returns_null_for_a_missing_s3_image_without_listing_the_bucket(string $type): void
    {
        config(['account.images.storage' => 's3']);
        $this->useS3HttpFixture(static fn (RequestInterface $request) => new Response($request->getMethod() === 'HEAD' ? 404 : 403));
        $service = new AccountImageUrlService;
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');

        $url = $type === 'icon' ? $service->iconImageUrl($identifier) : $service->headerImageUrl($identifier);

        self::assertNull($url);
        self::assertCount(1, $this->s3Requests);
        self::assertSame('HEAD', $this->s3Requests[0]->getMethod());
        self::assertSame("/fixture-bucket/accounts/{$identifier->value()}/{$type}/{$type}.webp", $this->s3Requests[0]->getUri()->getPath());
    }

    #[DataProvider('imageTypes')]
    public function test_returns_a_one_hour_signed_url_for_an_existing_s3_image(string $type): void
    {
        $this->freezeTime();
        config(['account.images.storage' => 's3']);
        $this->useS3HttpFixture(static fn () => new Response(200));
        $service = new AccountImageUrlService;
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');

        $url = $type === 'icon' ? $service->iconImageUrl($identifier) : $service->headerImageUrl($identifier);

        self::assertIsString($url);
        self::assertSame('https', parse_url($url, PHP_URL_SCHEME));
        self::assertSame('s3.fixture.invalid', parse_url($url, PHP_URL_HOST));
        self::assertSame("/fixture-bucket/accounts/{$identifier->value()}/{$type}/{$type}.webp", parse_url($url, PHP_URL_PATH));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame('AWS4-HMAC-SHA256', $query['X-Amz-Algorithm']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $query['X-Amz-Signature']);
        $signedAt = DateTimeImmutable::createFromFormat('Ymd\THis\Z', $query['X-Amz-Date'], new DateTimeZone('UTC'));
        self::assertInstanceOf(DateTimeImmutable::class, $signedAt);
        self::assertSame(now()->addHour()->getTimestamp(), $signedAt->getTimestamp() + (int) $query['X-Amz-Expires']);
        self::assertCount(1, $this->s3Requests);
        self::assertSame('HEAD', $this->s3Requests[0]->getMethod());
    }

    #[DataProvider('imageTypes')]
    public function test_does_not_treat_a_forbidden_s3_head_as_a_missing_image(string $type): void
    {
        config(['account.images.storage' => 's3']);
        $this->useS3HttpFixture(static fn () => new Response(403));
        $service = new AccountImageUrlService;
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');

        try {
            if ($type === 'icon') {
                $service->iconImageUrl($identifier);
            } else {
                $service->headerImageUrl($identifier);
            }
            self::fail('A forbidden HEAD must not become a null image URL.');
        } catch (UnableToCheckFileExistence $exception) {
            self::assertInstanceOf(S3Exception::class, $exception->getPrevious());
            self::assertSame(403, $exception->getPrevious()->getStatusCode());
        }

        self::assertCount(1, $this->s3Requests);
        self::assertSame('HEAD', $this->s3Requests[0]->getMethod());
    }

    #[DataProvider('imageTypes')]
    public function test_preserves_null_and_one_hour_signed_urls_for_the_local_driver(string $type): void
    {
        $this->freezeTime();
        $root = sys_get_temp_dir().'/hibilio-local-images-'.bin2hex(random_bytes(8));
        config(['account.images.storage' => 'local', 'filesystems.disks.account_images.root' => $root]);
        Storage::forgetDisk('account_images');
        $disk = Storage::disk('account_images');
        $service = new AccountImageUrlService;
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $path = "accounts/{$identifier->value()}/{$type}/{$type}.webp";

        try {
            $missing = $type === 'icon' ? $service->iconImageUrl($identifier) : $service->headerImageUrl($identifier);
            self::assertNull($missing);
            $disk->put($path, 'fixture-image');

            $url = $type === 'icon' ? $service->iconImageUrl($identifier) : $service->headerImageUrl($identifier);

            self::assertIsString($url);
            self::assertSame('/account-images/'.$path, parse_url($url, PHP_URL_PATH));
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            self::assertSame((string) now()->addHour()->getTimestamp(), $query['expires']);
            self::assertTrue(URL::hasValidSignature(Request::create($url), absolute: false));
        } finally {
            $disk->deleteDirectory('accounts');
            rmdir($root);
        }
    }

    /** @return array<string, array{string}> */
    public static function imageTypes(): array
    {
        return ['icon' => ['icon'], 'header' => ['header']];
    }
}
