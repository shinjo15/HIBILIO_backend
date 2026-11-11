<?php

declare(strict_types=1);

namespace Tests\Unit\Routine\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;

final class RoutineVideoUrlTest extends TestCase
{
    public function test_constructor_rejects_non_youtube_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RoutineVideoUrl('https://example.com/video');
    }

    public function test_preserves_length_boundary(): void
    {
        $prefix = 'https://youtu.be/dQw4w9WgXcQ?x=';
        $url = $prefix.str_repeat('a', 2048 - strlen($prefix));
        $video = new RoutineVideoUrl($url);
        self::assertSame($url, $video->value());
        $this->expectException(\InvalidArgumentException::class);
        new RoutineVideoUrl($url.'a');
    }

    #[DataProvider('validUrls')]
    public function test_accepts_supported_youtube_urls(string $url): void
    {
        self::assertSame($url, (new RoutineVideoUrl($url))->value());
    }

    public static function validUrls(): array
    {
        return array_map(static fn (string $url): array => [$url], [
            'http://youtube.com/watch?v=dQw4w9WgXcQ&t=12',
            'https://WWW.YOUTUBE.COM/watch?list=abc&v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ?title=日本語',
            'https://m.youtube.com/shorts/dQw4w9WgXcQ',
            'https://youtube.com/live/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ]);
    }

    #[DataProvider('invalidBrowserHosts')]
    public function test_rejects_browser_invalid_numeric_hosts(string $host): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RoutineVideoUrl('http://'.$host.'/');
    }

    public static function invalidBrowserHosts(): array
    {
        return array_map(static fn (string $host): array => [$host], [
            '1.2.3.08', '1.2.3.08.', '09', '999999999999', '4294967296',
            '0x100000000', '1.16777216', '1.2.65536', '1.2.3.256',
            '256.1', '0400.1', '0x100.1', '1.2.3.4.5', 'example.123',
        ]);
    }

    #[DataProvider('invalidUrls')]
    public function test_rejects_invalid_urls(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RoutineVideoUrl($url);
    }

    public static function invalidUrls(): array
    {
        return array_map(static fn (string $value): array => [$value], [
            '', '0', ' ', 'javascript:alert(1)', 'ftp://youtu.be/dQw4w9WgXcQ',
            'https://127.0.0.999/', 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ',
            'https://user@youtube.com/watch?v=dQw4w9WgXcQ', 'https://youtube.com:443/watch?v=dQw4w9WgXcQ',
            'https://youtube.com/watch?v=dQw4w9WgXcQ&v=bad', 'https://youtube.com/watch?v=dQw4w9WgXcQ&v%5B%5D=bad',
            'https://youtu.be/dQw4w9WgXcQ?x=%zz', 'https://youtu.be/dQw4w9WgXcQ?x=<script>',
            'https://youtube.com/shorts/dQw4w9WgXcQ/extra', 'https://youtube.com/watch?v=short',
        ]);
    }
}
