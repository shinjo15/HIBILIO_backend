<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Infrastructure\Service;

use InvalidArgumentException;
use Src\Account\Infrastructure\Service\AccountImageConverterService;
use Tests\TestCase;

final class AccountImageConverterServiceTest extends TestCase
{
    public function test_rejects_an_image_that_cannot_be_decoded(): void
    {
        $contents = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', 2048, 2048)."\x08\x06\x00\x00\x00"."\x00\x00\x00\x00";

        $this->expectException(InvalidArgumentException::class);

        (new AccountImageConverterService)->convertToIcon($contents);
    }
}
