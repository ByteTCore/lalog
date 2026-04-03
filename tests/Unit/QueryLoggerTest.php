<?php

namespace Lalog\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Lalog\QueryLogger;
use Lalog\Tests\TestCase;

class QueryLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Create test table for binding tests
        \DB::statement('CREATE TABLE IF NOT EXISTS test_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, age INTEGER, score REAL, is_active INTEGER DEFAULT 1)');
    }

    protected function tearDown(): void
    {
        \DB::statement('DROP TABLE IF EXISTS test_users');
        parent::tearDown();
    }

    // ─── Resolution ───

    public function test_query_logger_can_be_resolved(): void
    {
        $logger = $this->app->make(QueryLogger::class);

        $this->assertInstanceOf(QueryLogger::class, $logger);
    }

    // ─── File Creation ───

    public function test_listen_creates_log_file(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        $date = date('Y-m-d');
        $expectedFile = "query/sql-{$date}.sql";

        \DB::select('SELECT 1');

        Storage::disk('local')->assertExists($expectedFile);
    }

    public function test_file_created_even_without_queries(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        // File should exist with START separator even without queries
        $this->assertStringContainsString('----------START---------', $content);
    }

    // ─── Log Content ───

    public function test_log_file_contains_query(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('SELECT 1', $content);
        $this->assertStringContainsString('Date:', $content);
        $this->assertStringContainsString('Time query:', $content);
    }

    public function test_log_contains_separator(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('----------START---------', $content);
        $this->assertStringContainsString('----------END----------', $content);
    }

    public function test_log_contains_date_in_correct_format(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        // Date line should match Y-m-d H:i:s format
        $this->assertMatchesRegularExpression('/Date: \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $content);
    }

    public function test_log_contains_query_time_in_ms(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertMatchesRegularExpression('/Time query: [\d.]+\(ms\)/', $content);
    }

    public function test_query_ends_with_semicolon(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertMatchesRegularExpression('/SELECT 1;/', $content);
    }

    // ─── Multiple Queries ───

    public function test_multiple_queries_logged_in_same_file(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');
        \DB::select('SELECT 2');
        \DB::select('SELECT 3');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('SELECT 1', $content);
        $this->assertStringContainsString('SELECT 2', $content);
        $this->assertStringContainsString('SELECT 3', $content);
    }

    public function test_multiple_queries_each_have_end_separator(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');
        \DB::select('SELECT 2');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        // Count END separators — should be 2 (one per query)
        $endCount = substr_count($content, '----------END----------');
        $this->assertEquals(2, $endCount);
    }

    public function test_start_separator_appears_only_once(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');
        \DB::select('SELECT 2');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $startCount = substr_count($content, '----------START---------');
        $this->assertEquals(1, $startCount);
    }

    // ─── Bindings ───

    public function test_string_bindings_are_interpolated(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => 'John', 'email' => 'john@test.com']);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('name', 'John')->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString("'John'", $content);
    }

    public function test_integer_bindings_are_interpolated(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => 'Alice', 'age' => 25]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('age', 25)->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('25', $content);
    }

    public function test_multiple_bindings_are_interpolated(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => 'Bob', 'email' => 'bob@test.com', 'age' => 30]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('name', 'Bob')->where('age', 30)->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString("'Bob'", $content);
        $this->assertStringContainsString('30', $content);
    }

    public function test_query_without_bindings(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('SELECT 1;', $content);
    }

    public function test_sql_with_percent_sign_is_handled(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => 'Johnson']);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('name', 'like', '%John%')->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('%John%', $content);
    }

    // ─── Clear on Start ───

    public function test_clear_on_start_deletes_existing_file(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => true]);

        $date = date('Y-m-d');
        $filePath = "query/sql-{$date}.sql";

        Storage::disk('local')->put($filePath, 'old content');
        Storage::disk('local')->assertExists($filePath);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        $content = Storage::disk('local')->get($filePath);
        $this->assertStringNotContainsString('old content', $content);
    }

    public function test_clear_on_start_false_preserves_existing_content(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $date = date('Y-m-d');
        $filePath = "query/sql-{$date}.sql";

        Storage::disk('local')->put($filePath, 'existing content');

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $content = Storage::disk('local')->get($filePath);

        // Both old and new content should be present
        $this->assertStringContainsString('existing content', $content);
        $this->assertStringContainsString('SELECT 1', $content);
    }

    public function test_clear_on_start_when_no_file_exists(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => true]);

        $date = date('Y-m-d');
        $filePath = "query/sql-{$date}.sql";

        // No file exists, should not throw error
        Storage::disk('local')->assertMissing($filePath);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        // After listen, should have START separator
        $content = Storage::disk('local')->get($filePath);
        $this->assertStringContainsString('----------START---------', $content);
    }

    // ─── File Rotation ───

    public function test_file_rotation_creates_new_file_when_max_size_reached(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.max_size' => 10, // Very small limit to trigger rotation
        ]);

        $date = date('Y-m-d');
        $filePath = "query/sql-{$date}.sql";

        // Create a file that exceeds max_size
        Storage::disk('local')->put($filePath, str_repeat('X', 20));

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        // Should create rotated file
        Storage::disk('local')->assertExists("query/sql-{$date}-1.sql");
    }

    public function test_file_rotation_increments_index(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.max_size' => 10,
        ]);

        $date = date('Y-m-d');

        // Fill first two files beyond max_size
        Storage::disk('local')->put("query/sql-{$date}.sql", str_repeat('X', 20));
        Storage::disk('local')->put("query/sql-{$date}-1.sql", str_repeat('X', 20));

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        Storage::disk('local')->assertExists("query/sql-{$date}-2.sql");
    }

    public function test_no_rotation_when_file_under_max_size(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.max_size' => 2000000,
        ]);

        $date = date('Y-m-d');
        $filePath = "query/sql-{$date}.sql";

        // Create small file
        Storage::disk('local')->put($filePath, 'small content');

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        // Should append to existing file, not create rotated one
        Storage::disk('local')->assertMissing("query/sql-{$date}-1.sql");
        $content = Storage::disk('local')->get($filePath);
        $this->assertStringContainsString('SELECT 1', $content);
    }

    // ─── Custom Config ───

    public function test_custom_format(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.format' => 'debug-{date}',
        ]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        Storage::disk('local')->assertExists("query/debug-{$date}.sql");
    }

    public function test_custom_extension(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.extension' => 'log',
        ]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        Storage::disk('local')->assertExists("query/sql-{$date}.log");
    }

    public function test_custom_directory(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.directory' => 'logs/sql',
        ]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        Storage::disk('local')->assertExists("logs/sql/sql-{$date}.sql");
    }

    public function test_custom_date_format(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.date_format' => 'Y_m_d',
        ]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y_m_d');
        Storage::disk('local')->assertExists("query/sql-{$date}.sql");
    }

    public function test_custom_date_format_with_hour(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.date_format' => 'Y-m-d_H',
        ]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d_H');
        Storage::disk('local')->assertExists("query/sql-{$date}.sql");
    }

    public function test_format_without_date_placeholder(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.format' => 'queries',
        ]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        Storage::disk('local')->assertExists('query/queries.sql');
    }

    public function test_custom_separators(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.separator_start' => '=== START ===',
            'lalog.separator_end' => '=== END ===',
        ]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('=== START ===', $content);
        $this->assertStringContainsString('=== END ===', $content);
    }

    // ─── Complex Queries ───

    public function test_insert_query_is_logged(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->insert(['name' => 'TestUser', 'email' => 'test@test.com']);

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('insert', strtolower($content));
        $this->assertStringContainsString("'TestUser'", $content);
        $this->assertStringContainsString("'test@test.com'", $content);
    }

    public function test_update_query_is_logged(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => 'OldName']);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('name', 'OldName')->update(['name' => 'NewName']);

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('update', strtolower($content));
        $this->assertStringContainsString("'NewName'", $content);
    }

    public function test_delete_query_is_logged(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => 'ToDelete']);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('name', 'ToDelete')->delete();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('delete', strtolower($content));
        $this->assertStringContainsString("'ToDelete'", $content);
    }

    public function test_join_query_is_logged(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::statement('CREATE TABLE IF NOT EXISTS test_posts (id INTEGER PRIMARY KEY, user_id INTEGER, title TEXT)');

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')
            ->join('test_posts', 'test_users.id', '=', 'test_posts.user_id')
            ->select('test_users.name', 'test_posts.title')
            ->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('join', strtolower($content));
        $this->assertStringContainsString('test_posts', $content);

        \DB::statement('DROP TABLE IF EXISTS test_posts');
    }

    public function test_aggregate_query_is_logged(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->count();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('count', strtolower($content));
    }

    // ─── Edge Cases ───

    public function test_special_characters_in_bindings(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => "O'Brien"]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('name', "O'Brien")->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString("O'Brien", $content);
    }

    public function test_empty_string_binding(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('name', '')->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString("''", $content);
    }

    public function test_null_handling_in_query(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->whereNull('email')->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('null', strtolower($content));
    }

    public function test_all_config_options_combined(): void
    {
        config([
            'lalog.enabled' => true,
            'lalog.clear_on_start' => false,
            'lalog.disk' => 'local',
            'lalog.directory' => 'custom/logs',
            'lalog.format' => 'app-{date}',
            'lalog.date_format' => 'Ymd',
            'lalog.extension' => 'txt',
            'lalog.max_size' => 5000000,
        ]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select('SELECT 1');

        $date = date('Ymd');
        Storage::disk('local')->assertExists("custom/logs/app-{$date}.txt");
    }

    // ─── Cross-Database Binding Types ───

    public function test_boolean_true_binding(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('is_active', true)->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('TRUE', $content);
    }

    public function test_boolean_false_binding(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('is_active', false)->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('FALSE', $content);
    }

    public function test_integer_binding_not_quoted(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('age', 25)->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        // Integer should appear as 25, not '25'
        $this->assertMatchesRegularExpression('/= 25/', $content);
    }

    public function test_float_binding_not_quoted(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->where('score', 99.5)->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('99.5', $content);
    }

    public function test_where_in_with_multiple_bindings(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => 'A']);
        \DB::table('test_users')->insert(['name' => 'B']);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::table('test_users')->whereIn('name', ['A', 'B', 'C'])->get();

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString("'A'", $content);
        $this->assertStringContainsString("'B'", $content);
        $this->assertStringContainsString("'C'", $content);
    }

    public function test_query_without_bindings_no_error(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        // Raw query with no bindings
        \DB::select('SELECT 1 + 1 AS result');

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('SELECT 1 + 1 AS result', $content);
    }

    public function test_datetime_object_binding(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        $now = new \DateTime('2026-01-15 10:30:00');
        \DB::select('SELECT * FROM test_users WHERE id > ?', [$now]);

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString("'2026-01-15 10:30:00'", $content);
    }

    public function test_carbon_object_binding(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        $carbon = \Carbon\Carbon::parse('2026-06-15 14:00:00');
        \DB::select('SELECT * FROM test_users WHERE id > ?', [$carbon]);

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString("'2026-06-15 14:00:00'", $content);
    }

    public function test_mixed_binding_types(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        \DB::table('test_users')->insert(['name' => 'MixTest', 'age' => 30, 'is_active' => 1]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        \DB::select(
            'SELECT * FROM test_users WHERE name = ? AND age > ? AND is_active = ?',
            ['MixTest', 20, true]
        );

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString("'MixTest'", $content);
        $this->assertStringContainsString('20', $content);
        $this->assertStringContainsString('TRUE', $content);
    }

    public function test_array_binding_is_json_encoded(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        // Dispatch event directly to avoid real PDO exceptions for invalid array binding in SQLite
        $event = new \Illuminate\Database\Events\QueryExecuted(
            'SELECT * FROM test_users WHERE meta = ?',
            [['key' => 'value', 'count' => 1]],
            0.5,
            \DB::connection()
        );
        event($event);

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        $this->assertStringContainsString('\'{"key":"value","count":1}\'', $content);
    }

    public function test_mismatched_placeholders_and_bindings(): void
    {
        config(['lalog.enabled' => true, 'lalog.clear_on_start' => false]);

        $logger = $this->app->make(QueryLogger::class);
        $logger->listen();

        // Dispatch manually to test the logger's safe string formatting without crashing PDO
        $event = new \Illuminate\Database\Events\QueryExecuted(
            'SELECT * FROM test_users WHERE id = ?',
            [1, 2],
            0.5,
            \DB::connection()
        );
        event($event);

        $date = date('Y-m-d');
        $content = Storage::disk('local')->get("query/sql-{$date}.sql");

        // Should fallback to showing raw binding data as a comment
        $this->assertStringContainsString('bindings: [1, 2]', $content);
        $this->assertStringContainsString('WHERE id = ?', $content); // Placeholder should remain unreplaced
    }
}
