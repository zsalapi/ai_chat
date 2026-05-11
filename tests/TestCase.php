<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        if (\Illuminate\Support\Facades\Schema::hasTable('oauth_clients')) {
            \Illuminate\Support\Facades\Artisan::call('passport:client', ['--personal' => true, '--name' => 'TestClient', '--no-interaction' => true]);
        }
    }
}
