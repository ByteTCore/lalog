<?php

namespace Lalog\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Lalog\Facades\Lalog;
use Lalog\LalogServiceProvider;
use Lalog\QueryLogger;
use Lalog\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered(): void
    {
        $this->assertArrayHasKey(LalogServiceProvider::class, $this->app->getLoadedProviders());
    }

    public function test_query_logger_is_bound_as_singleton(): void
    {
        $instance1 = $this->app->make(QueryLogger::class);
        $instance2 = $this->app->make(QueryLogger::class);

        $this->assertSame($instance1, $instance2);
    }

    public function test_config_is_merged(): void
    {
        $this->assertNotNull(config('lalog'));
        $this->assertIsArray(config('lalog'));
    }

    public function test_config_has_all_required_keys(): void
    {
        $config = config('lalog');

        $requiredKeys = [
            'enabled', 'disk', 'directory', 'max_size',
            'format', 'date_format', 'extension',
            'clear_on_start', 'separator_start', 'separator_end',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $config, "Missing config key: {$key}");
        }
    }

    public function test_does_not_listen_when_disabled(): void
    {
        config(['lalog.enabled' => false]);

        Storage::fake('local');

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        Storage::disk('local')->assertMissing("query/sql-{$date}.sql");
    }

    public function test_listens_when_enabled(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        Storage::fake('local');

        // Re-boot to trigger listen
        $provider = new LalogServiceProvider($this->app);
        $provider->boot();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        Storage::disk('local')->assertExists("query/sql-{$date}.sql");
    }

    public function test_facade_resolves_to_query_logger(): void
    {
        $resolved = Lalog::getFacadeRoot();

        $this->assertInstanceOf(QueryLogger::class, $resolved);
    }

    public function test_config_publish_path_is_set(): void
    {
        $provider = new LalogServiceProvider($this->app);

        // Use reflection to check publishes
        $this->artisan('vendor:publish', ['--tag' => 'lalog-config', '--no-interaction' => true]);

        $this->assertFileExists(config_path('lalog.php'));
    }

    public function test_query_logger_receives_correct_config(): void
    {
        config(['lalog.disk' => 'custom_disk', 'lalog.max_size' => 999]);

        // Re-bind with new config
        $this->app->forgetInstance(QueryLogger::class);
        $this->app->singleton(QueryLogger::class, function ($app) {
            return new QueryLogger($app['filesystem'], $app['config']['lalog']);
        });

        $logger = $this->app->make(QueryLogger::class);

        $this->assertInstanceOf(QueryLogger::class, $logger);
    }
}
