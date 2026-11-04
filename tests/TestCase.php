<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\PostgresDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $application = parent::createApplication();

        if (defined('HIBILIO_POSTGRES_REGRESSION')) {
            PostgresDatabaseGuard::assertConnection($application);
        }

        return $application;
    }
}
