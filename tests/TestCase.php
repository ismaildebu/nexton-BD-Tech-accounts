<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('NOW', function () {
                return date('Y-m-d H:i:s');
            });
        }
    }
}