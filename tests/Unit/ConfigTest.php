<?php

namespace Lalog\Tests\Unit;

use Lalog\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_config_is_loaded(): void
    {
        $this->assertNotNull(config('lalog'));
    }

    public function test_config_is_array(): void
    {
        $this->assertIsArray(config('lalog'));
    }

    public function test_default_enabled_is_false(): void
    {
        $this->assertFalse(config('lalog.enabled'));
    }

    public function test_default_disk_is_local(): void
    {
        $this->assertEquals('local', config('lalog.disk'));
    }

    public function test_default_directory(): void
    {
        $this->assertEquals('query', config('lalog.directory'));
    }

    public function test_default_max_size(): void
    {
        $this->assertEquals(2000000, config('lalog.max_size'));
    }

    public function test_default_max_size_is_integer(): void
    {
        $this->assertIsInt(config('lalog.max_size'));
    }

    public function test_default_format(): void
    {
        $this->assertEquals('sql-{date}', config('lalog.format'));
    }

    public function test_default_format_contains_date_placeholder(): void
    {
        $this->assertStringContainsString('{date}', config('lalog.format'));
    }

    public function test_default_date_format(): void
    {
        $this->assertEquals('Y-m-d', config('lalog.date_format'));
    }

    public function test_default_extension(): void
    {
        $this->assertEquals('sql', config('lalog.extension'));
    }

    public function test_default_clear_on_start_is_true(): void
    {
        $this->assertTrue(config('lalog.clear_on_start'));
    }

    public function test_default_separator_start(): void
    {
        $this->assertEquals('----------START---------', config('lalog.separator_start'));
    }

    public function test_default_separator_end(): void
    {
        $this->assertEquals('----------END----------', config('lalog.separator_end'));
    }

    // ─── Override Tests ───

    public function test_enabled_can_be_overridden(): void
    {
        config(['lalog.enabled' => true]);
        $this->assertTrue(config('lalog.enabled'));
    }

    public function test_disk_can_be_overridden(): void
    {
        config(['lalog.disk' => 's3']);
        $this->assertEquals('s3', config('lalog.disk'));
    }

    public function test_directory_can_be_overridden(): void
    {
        config(['lalog.directory' => 'custom/logs']);
        $this->assertEquals('custom/logs', config('lalog.directory'));
    }

    public function test_max_size_can_be_overridden(): void
    {
        config(['lalog.max_size' => 5000000]);
        $this->assertEquals(5000000, config('lalog.max_size'));
    }

    public function test_format_can_be_overridden(): void
    {
        config(['lalog.format' => 'query-{date}']);
        $this->assertEquals('query-{date}', config('lalog.format'));
    }

    public function test_date_format_can_be_overridden(): void
    {
        config(['lalog.date_format' => 'Y_m_d_H']);
        $this->assertEquals('Y_m_d_H', config('lalog.date_format'));
    }

    public function test_extension_can_be_overridden(): void
    {
        config(['lalog.extension' => 'log']);
        $this->assertEquals('log', config('lalog.extension'));
    }

    public function test_clear_on_start_can_be_overridden(): void
    {
        config(['lalog.clear_on_start' => false]);
        $this->assertFalse(config('lalog.clear_on_start'));
    }

    public function test_separator_start_can_be_overridden(): void
    {
        config(['lalog.separator_start' => '=== BEGIN ===']);
        $this->assertEquals('=== BEGIN ===', config('lalog.separator_start'));
    }

    public function test_separator_end_can_be_overridden(): void
    {
        config(['lalog.separator_end' => '=== FINISH ===']);
        $this->assertEquals('=== FINISH ===', config('lalog.separator_end'));
    }

    // ─── Multiple overrides together ───

    public function test_multiple_config_overrides_at_once(): void
    {
        config([
            'lalog.disk' => 'public',
            'lalog.max_size' => 1000000,
            'lalog.format' => 'app-{date}',
            'lalog.date_format' => 'Ymd',
            'lalog.extension' => 'txt',
            'lalog.directory' => 'app-logs',
        ]);

        $this->assertEquals('public', config('lalog.disk'));
        $this->assertEquals(1000000, config('lalog.max_size'));
        $this->assertEquals('app-{date}', config('lalog.format'));
        $this->assertEquals('Ymd', config('lalog.date_format'));
        $this->assertEquals('txt', config('lalog.extension'));
        $this->assertEquals('app-logs', config('lalog.directory'));
    }

    public function test_override_does_not_affect_other_keys(): void
    {
        $originalDisk = config('lalog.disk');

        config(['lalog.max_size' => 999]);

        // disk should remain unchanged
        $this->assertEquals($originalDisk, config('lalog.disk'));
        $this->assertEquals(999, config('lalog.max_size'));
    }
}
