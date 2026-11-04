<?php

declare(strict_types=1);

$connection = @fsockopen('127.0.0.1', 9000, $errorCode, $errorMessage, 3);

if ($connection === false) {
    exit(1);
}

fclose($connection);
