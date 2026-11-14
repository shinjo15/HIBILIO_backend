<?php

declare(strict_types=1);

namespace Src\ListeningContent\Domain\Exception;

final class InvalidListeningContentUrlException extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('コンテンツのURLは有効な形式である必要があります。');
    }
}
