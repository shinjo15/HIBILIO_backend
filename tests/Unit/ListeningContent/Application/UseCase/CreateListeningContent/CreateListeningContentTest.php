<?php

declare(strict_types=1);

namespace Tests\Unit\ListeningContent\Application\UseCase\CreateListeningContent;

use PHPUnit\Framework\TestCase;
use Src\ListeningContent\Application\UseCase\CreateListeningContent\CreateListeningContent;
use Src\ListeningContent\Application\UseCase\CreateListeningContent\CreateListeningContentInput;
use Src\ListeningContent\Domain\Entity\ListeningContent;
use Src\ListeningContent\Domain\Repository\ListeningContentRepositoryInterface;
use Src\ListeningContent\Domain\ValueObject\ListeningContentUrl;
use Src\ListeningContent\Infrastructure\Factory\ListeningContentFactory;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class CreateListeningContentTest extends TestCase
{
    public function test_creates_each_content_with_the_supplied_account_and_url(): void
    {
        $uuid = $this->createStub(UuidServiceInterface::class);
        $uuid->method('generate')->willReturnOnConsecutiveCalls('33333333-3333-4333-8333-333333333333', '44444444-4444-4444-8444-444444444444');
        $repository = new InMemoryListeningContentRepository;
        $usecase = new CreateListeningContent(new ListeningContentFactory($uuid), $repository);
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        foreach (['https://example.com/audio', 'https://open.spotify.com/track/example'] as $url) {
            $usecase->execute(new CreateListeningContentInput($account, new ListeningContentUrl($url)));
        }

        self::assertCount(2, $repository->saved);
        self::assertSame('11111111-1111-4111-8111-111111111111', $repository->saved[0]->accountIdentifier()->value());
        self::assertSame('https://example.com/audio', $repository->saved[0]->url()->value());
        self::assertSame('https://open.spotify.com/track/example', $repository->saved[1]->url()->value());
        self::assertNotSame($repository->saved[0]->identifier()->value(), $repository->saved[1]->identifier()->value());
    }
}

final class InMemoryListeningContentRepository implements ListeningContentRepositoryInterface
{
    public array $saved = [];

    public function save(ListeningContent $content): void
    {
        $this->saved[] = $content;
    }
}
