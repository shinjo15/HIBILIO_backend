<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Infrastructure\Service;

use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Account\Infrastructure\Service\S3StorageService;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\Support\InteractsWithS3HttpFixture;
use Tests\TestCase;

final class S3StorageServiceTest extends TestCase
{
    use InteractsWithS3HttpFixture;

    #[DataProvider('uploadImages')]
    public function test_uploads_webp_without_any_acl_header(string $type, bool $atSizeLimit): void
    {
        $this->useS3HttpFixture(static fn () => new Response(200, ['ETag' => '"fixture"']));
        $image = $this->image($type, $atSizeLimit);
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $service = new S3StorageService;

        if ($image instanceof AccountIcon) {
            $service->uploadIcon($identifier, $image);
        } else {
            $service->uploadHeader($identifier, $image);
        }

        self::assertCount(1, $this->s3Requests);
        $request = $this->s3Requests[0];
        self::assertSame('PUT', $request->getMethod());
        self::assertSame("/fixture-bucket/accounts/{$identifier->value()}/{$type}/{$type}.webp", $request->getUri()->getPath());
        self::assertFalse($request->hasHeader('x-amz-acl'));
        self::assertSame('image/webp', $request->getHeaderLine('Content-Type'));
        self::assertSame($image->contents(), (string) $request->getBody());
    }

    #[DataProvider('imageTypes')]
    public function test_propagates_an_upload_failure(string $type): void
    {
        $this->useS3HttpFixture(static fn () => new Response(403, [], '<Error><Code>AccessDenied</Code><Message>fixture</Message></Error>'));
        $image = $this->image($type);
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $service = new S3StorageService;

        try {
            if ($image instanceof AccountIcon) {
                $service->uploadIcon($identifier, $image);
            } else {
                $service->uploadHeader($identifier, $image);
            }
            self::fail('S3 upload errors must propagate.');
        } catch (UnableToWriteFile $exception) {
            self::assertInstanceOf(S3Exception::class, $exception->getPrevious());
            self::assertSame(403, $exception->getPrevious()->getStatusCode());
        }

        self::assertCount(1, $this->s3Requests);
        self::assertFalse($this->s3Requests[0]->hasHeader('x-amz-acl'));
    }

    #[DataProvider('imageTypes')]
    public function test_deletes_only_the_requested_image_key(string $type): void
    {
        $this->useS3HttpFixture(static fn () => new Response(204));
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $service = new S3StorageService;

        if ($type === 'icon') {
            $service->deleteIcon($identifier);
        } else {
            $service->deleteHeader($identifier);
        }

        self::assertCount(1, $this->s3Requests);
        self::assertSame('DELETE', $this->s3Requests[0]->getMethod());
        self::assertSame("/fixture-bucket/accounts/{$identifier->value()}/{$type}/{$type}.webp", $this->s3Requests[0]->getUri()->getPath());
    }

    #[DataProvider('imageTypes')]
    public function test_propagates_a_delete_failure(string $type): void
    {
        $this->useS3HttpFixture(static fn () => new Response(403, [], '<Error><Code>AccessDenied</Code><Message>fixture</Message></Error>'));
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $service = new S3StorageService;

        $this->expectException(UnableToDeleteFile::class);

        if ($type === 'icon') {
            $service->deleteIcon($identifier);
        } else {
            $service->deleteHeader($identifier);
        }
    }

    /** @return array<string, array{string, bool}> */
    public static function uploadImages(): array
    {
        return [
            'icon' => ['icon', false],
            'header' => ['header', false],
            'icon size limit' => ['icon', true],
            'header size limit' => ['header', true],
        ];
    }

    /** @return array<string, array{string}> */
    public static function imageTypes(): array
    {
        return ['icon' => ['icon'], 'header' => ['header']];
    }

    private function image(string $type, bool $atSizeLimit = false): AccountIcon|AccountHeader
    {
        $image = imagecreatetruecolor($type === 'icon' ? 128 : 640, $type === 'icon' ? 128 : 320);
        ob_start();
        imagewebp($image);
        $contents = ob_get_clean();
        imagedestroy($image);
        self::assertIsString($contents);

        if ($atSizeLimit) {
            $contents = str_pad($contents, ($type === 'icon' ? 5 : 10) * 1024 * 1024, "\0");
        }

        return $type === 'icon' ? new AccountIcon($contents) : new AccountHeader($contents);
    }
}
