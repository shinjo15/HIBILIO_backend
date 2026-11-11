<?php

declare(strict_types=1);

namespace Src\Routine\Domain\Exception;

final class InvalidYouTubeVideoUrlException extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('YouTubeの動画URLを指定してください。');
    }
}
