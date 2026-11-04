<?php

declare(strict_types=1);
use Tests\Support\PostgresDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

PostgresDatabaseGuard::prepareEnvironment();

define('HIBILIO_POSTGRES_REGRESSION', true);
