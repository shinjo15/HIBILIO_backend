<?php

declare(strict_types=1);

namespace Tests\Unit\Routine\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;

final class RoutineVideoUrlTest extends TestCase
{
    public function test_registration_enforces_youtube_without_changing_restored_values(): void
    {
        $legacy = new RoutineVideoUrl('https://example.com/video');
        self::assertSame('https://example.com/video', $legacy->value());
        $this->expectException(\InvalidArgumentException::class);
        $legacy->validateForRegistration();
    }

    public function test_registration_preserves_length_boundary(): void
    {
        $prefix = 'https://youtu.be/dQw4w9WgXcQ?x=';
        $url = $prefix.str_repeat('a', 2048 - strlen($prefix));
        $video = new RoutineVideoUrl($url);
        $video->validateForRegistration();
        self::assertSame($url, $video->value());
        $this->expectException(\InvalidArgumentException::class);
        new RoutineVideoUrl($url.'a');
    }

    public function test_accepts_http_urls_up_to_2048_characters(): void
    {
        $url = 'http://example.com/'.str_repeat('a', 2029);
        self::assertSame(2048, strlen($url));
        self::assertSame($url, (new RoutineVideoUrl($url))->value());
        self::assertSame('https://localhost/video', (new RoutineVideoUrl('https://localhost/video'))->value());
    }

    public function test_preserves_unicode_urls(): void
    {
        foreach (['https://例え.テスト/動画?q=日本語', 'https://example.com/日本語'] as $url) {
            self::assertSame($url, (new RoutineVideoUrl($url))->value());
        }
    }

    public function test_accepts_valid_ip_addresses(): void
    {
        foreach (['https://127.0.0.1/video', 'https://255.255.255.255/video', 'https://[::1]/video'] as $url) {
            self::assertSame($url, (new RoutineVideoUrl($url))->value());
        }
    }

    #[DataProvider('validBrowserHosts')]
    public function test_accepts_browser_valid_numeric_hosts(string $host): void
    {
        $url = 'http://'.$host.'/';
        self::assertSame($url, (new RoutineVideoUrl($url))->value());
    }

    public static function validBrowserHosts(): array
    {
        return array_map(static fn (string $host): array => [$host], [
            '127.1', '0177.0.0.1', '0x7f.1', '0X7F.1', '4294967295', '0xffffffff',
            '1.16777215', '1.2.65535', '1.2.3.255', '127.0.0.1.', '0x', '123.example',
            '1.2.3.0xg', '例え.テスト', '[::1]',
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
        return array_map(static fn (string $value): array => [$value], ['', '0', ' ', 'javascript:alert(1)', 'ftp://example.com', 'https://127.0.0.999/', 'https://999.999.999.999/', 'https://example.com/" onclick="x', 'https://example.com/<script>', 'https://example.com/%zz', 'https://example.com/'.str_repeat('a', 2030)]);
    }
}
