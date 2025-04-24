<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as TestBenchTestCase;

class TestCase extends TestBenchTestCase
{
    use WithWorkbench, RefreshDatabase;

    protected function defineEnvironment($app)
    {
//        $app->useStoragePath(realpath(__DIR__ . '/../workbench/storage'));
        tap($app['config'], function ($config) {
            $config->set('filesystems.default', 'storage');
            $config->set('filesystems.disks.storage', [
                'driver' => 'local',
                'root' => realpath(__DIR__ . '/../workbench/storage'),
                'permissions' => [
                    'file' => [
                        'public' => 0777,
                        'private' => 0600,
                    ],
                    'dir' => [
                        'public' => 0777,
                        'private' => 0700,
                    ],
                ],
            ]);
        });
    }
}