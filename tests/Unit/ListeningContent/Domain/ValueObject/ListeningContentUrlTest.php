<?php

declare(strict_types=1);

namespace Tests\Unit\ListeningContent\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\ListeningContent\Domain\Exception\InvalidListeningContentUrlException;
use Src\ListeningContent\Domain\ValueObject\ListeningContentUrl;

final class ListeningContentUrlTest extends TestCase
{
    #[DataProvider('validUrls')]
    public function test_retains_valid_urls(string $url): void
    {
        self::assertSame($url, (new ListeningContentUrl($url))->value());
    }

    public static function validUrls(): array
    {
        return [
            ['https://open.spotify.com/track/example'],
            ['https://music.apple.com/jp/album/example'],
            ['spotify://track/123'],
            ['https://example.com/audio'],
        ];
    }

    #[DataProvider('invalidUrls')]
    public function test_rejects_invalid_urls(string $url): void
    {
        try {
            new ListeningContentUrl($url);
            self::fail('Invalid URL must throw an exception.');
        } catch (InvalidListeningContentUrlException $exception) {
            self::assertSame('コンテンツのURLは有効な形式である必要があります。', $exception->getMessage());
        }
    }

    public static function invalidUrls(): array
    {
        return [['not-a-url'], ['https://']];
    }

    public function test_rejects_empty_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ListeningContentUrl('');
    }
}
