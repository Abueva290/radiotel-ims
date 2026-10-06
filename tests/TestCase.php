<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Safety check: tests empty the database they run on (RefreshDatabase),
     * so they must NEVER run on the real radiotel_ims database.
     * phpunit.xml points them to radiotel_ims_testing; this stops the tests
     * if that setting is ignored (for example, when the config is cached).
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database   = $app['config']->get("database.connections.{$connection}.database");

        if ($database !== 'radiotel_ims_testing') {
            throw new RuntimeException(
                "Tests stopped: they would run on the \"{$database}\" database. "
                . 'Run "php artisan config:clear" and try again.'
            );
        }

        return $app;
    }
}