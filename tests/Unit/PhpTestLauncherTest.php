<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class PhpTestLauncherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/lims-launcher-fixture-'.bin2hex(random_bytes(12));
        mkdir($this->directory.'/scripts', 0700, true);
        mkdir($this->directory.'/runtime', 0700);
        $this->directory = realpath($this->directory);
        copy(dirname(__DIR__, 2).'/scripts/run-php-tests.php', $this->directory.'/scripts/run-php-tests.php');
        symlink(dirname(__DIR__, 2).'/vendor', $this->directory.'/vendor');
        file_put_contents($this->directory.'/artisan', <<<'PHP'
        <?php
        if ($argv[1] !== 'test') { exit(90); }
        $data = [
            'arguments' => array_slice($argv, 2), 'directory' => getcwd(),
            'scan_path' => getenv('PHP_INI_SCAN_DIR'), 'scanned' => php_ini_scanned_files(),
            'opcache' => opcache_get_status(false) !== false,
            'configuration' => array_map('ini_get', array_combine(
                ['memory_limit', 'error_reporting', 'display_errors', 'auto_prepend_file', 'precision', 'opcache.enable'],
                ['memory_limit', 'error_reporting', 'display_errors', 'auto_prepend_file', 'precision', 'opcache.enable']
            )),
            'extensions' => get_loaded_extensions(), 'main_ini' => php_ini_loaded_file(),
            'database' => getenv('DB_DATABASE'), 'marker' => getenv('LIMS_LAUNCHER_MARKER'),
            'stdin' => stream_get_contents(STDIN),
        ];
        echo json_encode($data, JSON_THROW_ON_ERROR);
        fwrite(STDERR, "fixture stderr\n");
        if (in_array('--fixture-exit=39', $argv, true)) { exit(39); }
        if (in_array('--fixture-wait', $argv, true)) { sleep(10); }
        PHP);
    }

    protected function tearDown(): void
    {
        try {
            $this->removeDirectory($this->directory);
        } finally {
            parent::tearDown();
        }
    }

    public function test_launcher_preserves_default_configuration_and_forwards_arguments_input_and_environment(): void
    {
        $environment = ['PHP_INI_SCAN_DIR' => false, 'TMPDIR' => $this->directory.'/runtime', 'LIMS_LAUNCHER_MARKER' => 'retained'];
        $baseline = $this->baseline($environment);
        $process = $this->launch(['--filter=space " quote $literal', '--compact', '--log-junit=path with spaces.xml'], $environment);
        $process->setInput("sample input\n");
        $process->run();
        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $data = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['--filter=space " quote $literal', '--compact', '--log-junit=path with spaces.xml'], $data['arguments']);
        $this->assertSame($this->directory, $data['directory']);
        $this->assertSame("sample input\n", $data['stdin']);
        $this->assertSame('retained', $data['marker']);
        $this->assertSame(getenv('DB_DATABASE'), $data['database']);
        $this->assertSame("fixture stderr\n", $process->getErrorOutput());
        $default = $this->defaultScanPath();
        $this->assertStringStartsWith(($default === '(none)' ? '' : $default.PATH_SEPARATOR).$this->directory.'/runtime/lims-php-tests-', $data['scan_path']);
        $this->assertPreservedRuntime($baseline, $data);
    }

    public function test_launcher_appends_to_explicit_scan_path_and_preserves_failure_exit_status(): void
    {
        mkdir($this->directory.'/custom ini', 0700);
        file_put_contents($this->directory.'/custom ini/20-custom.ini', "precision=13\nerror_reporting=32767\n");
        $scanPath = $this->defaultScanPath().PATH_SEPARATOR.$this->directory.'/custom ini';
        $environment = ['PHP_INI_SCAN_DIR' => $scanPath, 'TMPDIR' => $this->directory.'/runtime'];
        $baseline = $this->baseline($environment);
        $process = $this->launch(['--fixture-exit=39'], $environment);
        $process->run();
        $this->assertSame(39, $process->getExitCode(), $process->getErrorOutput());
        $data = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringStartsWith($scanPath.PATH_SEPARATOR, $data['scan_path']);
        $this->assertSame('13', $data['configuration']['precision']);
        $this->assertPreservedRuntime($baseline, $data);
    }

    public function test_launcher_refuses_disabled_opcache_without_starting_artisan(): void
    {
        mkdir($this->directory.'/disabled', 0700);
        file_put_contents($this->directory.'/disabled/99-disabled.ini', "opcache.enable=0\n");
        $process = $this->launch([], [
            'PHP_INI_SCAN_DIR' => $this->defaultScanPath().PATH_SEPARATOR.$this->directory.'/disabled',
            'TMPDIR' => $this->directory.'/runtime',
        ]);
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        $this->assertSame('', $process->getOutput());
        $this->assertStringContainsString('requires enabled OPcache', $process->getErrorOutput());
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    public function test_relative_scan_paths_keep_the_original_working_directory_meaning(): void
    {
        mkdir($this->directory.'/caller', 0700);
        mkdir($this->directory.'/custom ini', 0700);
        file_put_contents($this->directory.'/custom ini/20-custom.ini', "precision=13\n");
        $environment = [
            'PHP_INI_SCAN_DIR' => $this->defaultScanPath().PATH_SEPARATOR.'../custom ini',
            'TMPDIR' => $this->directory.'/runtime',
        ];
        $process = $this->launch([], $environment);
        $process->setWorkingDirectory($this->directory.'/caller');
        $process->mustRun();
        $data = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('13', $data['configuration']['precision']);
        $this->assertStringContainsString($this->directory.'/caller/../custom ini', $data['scan_path']);
        $this->assertTrue($data['opcache']);
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    public function test_relative_main_ini_remains_loaded_when_launcher_changes_directory(): void
    {
        mkdir($this->directory.'/caller', 0700);
        file_put_contents($this->directory.'/caller/php.ini', "precision=13\n");
        $process = $this->launch([], ['PHPRC' => 'php.ini', 'TMPDIR' => $this->directory.'/runtime']);
        $process->setWorkingDirectory($this->directory.'/caller');
        $process->mustRun();
        $data = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($this->directory.'/caller/php.ini', $data['main_ini']);
        $this->assertSame('13', $data['configuration']['precision']);
        $this->assertTrue($data['opcache']);
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    public function test_project_launcher_executes_real_artisan_and_phpunit_with_forwarded_report_arguments(): void
    {
        $root = dirname(__DIR__, 2);
        $reportPath = $this->directory.'/real artisan report.xml';
        $process = new Process([
            PHP_BINARY, $root.'/scripts/run-php-tests.php', '--compact',
            'tests/Unit/PhpCompilationCompatibilityTest.php', '--log-junit='.$reportPath,
        ], $this->directory, ['TMPDIR' => $this->directory.'/runtime'], timeout: 30);
        $process->run();
        $this->assertSame(0, $process->getExitCode(), mb_substr($process->getErrorOutput().$process->getOutput(), 0, 2000));
        $this->assertFileExists($reportPath);
        $report = simplexml_load_file($reportPath);
        $cases = $report->xpath('//testcase');
        $this->assertNotEmpty($cases);
        foreach ($cases as $case) {
            $this->assertSame(PhpCompilationCompatibilityTest::class, (string) $case['class']);
            $this->assertFalse(isset($case->failure) || isset($case->error) || isset($case->skipped));
        }
        $compiled = $report->xpath('//testcase[starts-with(@name, "test_application_sources_compile_without_diagnostics")]');
        $this->assertCount(count(PhpCompilationCompatibilityTest::applicationSources()), $compiled);
        foreach ($compiled as $case) {
            $this->assertSame(3, (int) $case['assertions']);
        }
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    #[DataProvider('phpConfigurationVariables')]
    public function test_launcher_refuses_dotenv_configuration_that_artisan_would_clear(string $name): void
    {
        file_put_contents($this->directory.'/.env', $name."=ignored\n");
        $process = $this->launch([], ['TMPDIR' => $this->directory.'/runtime']);
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        $this->assertSame('', $process->getOutput());
        $this->assertStringContainsString('not .env', $process->getErrorOutput());
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    /** @return array<string, array{string}> */
    public static function phpConfigurationVariables(): array
    {
        return ['scan path' => ['PHP_INI_SCAN_DIR'], 'main configuration' => ['PHPRC']];
    }

    public function test_malformed_dotenv_fails_without_printing_its_contents(): void
    {
        file_put_contents($this->directory.'/.env', 'SECRET="unclosed-private-value" trailing');
        $process = $this->launch([], ['TMPDIR' => $this->directory.'/runtime']);
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        $this->assertSame('', $process->getOutput());
        $this->assertStringContainsString('Cannot parse .env', $process->getErrorOutput());
        $this->assertStringNotContainsString('unclosed-private-value', $process->getErrorOutput());
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    public function test_failed_cache_probe_cleans_private_configuration_before_artisan_starts(): void
    {
        mkdir($this->directory.'/disabled probe', 0700);
        file_put_contents($this->directory.'/disabled probe/99-probe.ini', "disable_functions=opcache_get_status\n");
        $process = $this->launch([], [
            'PHP_INI_SCAN_DIR' => $this->defaultScanPath().PATH_SEPARATOR.$this->directory.'/disabled probe',
            'TMPDIR' => $this->directory.'/runtime',
        ]);
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        $this->assertSame('', $process->getOutput());
        $this->assertStringContainsString('CLI OPcache did not start', $process->getErrorOutput());
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    #[DataProvider('defaultCacheSettings')]
    public function test_launcher_refuses_cached_application_configuration_without_removing_it(?string $location, ?string $value): void
    {
        mkdir($this->directory.'/bootstrap/cache', 0700, true);
        file_put_contents($this->directory.'/bootstrap/cache/config.php', 'retained configuration');
        $environment = ['TMPDIR' => $this->directory.'/runtime'];
        if ($location === 'shell') {
            $environment['APP_CONFIG_CACHE'] = $value;
        } elseif ($location === 'dotenv') {
            file_put_contents($this->directory.'/.env', 'APP_CONFIG_CACHE='.$value);
        }
        $process = $this->launch([], $environment);
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        $this->assertSame('', $process->getOutput());
        $this->assertStringContainsString('configuration cache', $process->getErrorOutput());
        $this->assertSame('retained configuration', file_get_contents($this->directory.'/bootstrap/cache/config.php'));
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    /** @return array<string, array{?string, ?string}> */
    public static function defaultCacheSettings(): array
    {
        return [
            'default' => [null, null],
            'shell null' => ['shell', 'null'],
            'shell parenthesized null' => ['shell', '(null)'],
            'dotenv null' => ['dotenv', 'null'],
            'dotenv parenthesized null' => ['dotenv', '(null)'],
        ];
    }

    public function test_composer_entry_uses_the_launcher_without_a_process_timeout(): void
    {
        $configuration = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([
            'Composer\\Config::disableProcessTimeout', '@php scripts/run-php-tests.php',
        ], $configuration['scripts']['test']);
        $this->assertArrayNotHasKey('process-timeout', $configuration['config']);
    }

    #[DataProvider('customCacheLocations')]
    public function test_launcher_refuses_explicit_cached_configuration_without_removing_it(string $location): void
    {
        $cachePath = $this->directory.'/custom config.php';
        file_put_contents($cachePath, 'retained custom configuration');
        $environment = ['TMPDIR' => $this->directory.'/runtime'];
        if ($location === 'shell') {
            $environment['APP_CONFIG_CACHE'] = $cachePath;
        } else {
            file_put_contents($this->directory.'/.env', 'APP_CONFIG_CACHE="custom config.php"');
        }
        $process = $this->launch([], $environment);
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        $this->assertSame('', $process->getOutput());
        $this->assertStringContainsString('configuration cache', $process->getErrorOutput());
        $this->assertSame('retained custom configuration', file_get_contents($cachePath));
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    /** @return array<string, array{string}> */
    public static function customCacheLocations(): array
    {
        return ['absolute shell path' => ['shell'], 'relative dotenv path' => ['dotenv']];
    }

    #[DataProvider('terminationSignals')]
    public function test_launcher_forwards_termination_and_cleans_its_configuration(int $signal): void
    {
        $this->assertTrue(function_exists('pcntl_signal'), 'Signal coverage requires this project runtime to support pcntl.');
        $process = $this->launch(['--fixture-wait'], ['TMPDIR' => $this->directory.'/runtime']);
        $process->start();
        try {
            $deadline = microtime(true) + 5;
            while ($process->isRunning() && $process->getOutput() === '' && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertNotSame('', $process->getOutput(), $process->getErrorOutput());
            $data = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            $scanDirectories = explode(PATH_SEPARATOR, $data['scan_path']);
            $temporaryDirectory = end($scanDirectories);
            $this->assertDirectoryExists($temporaryDirectory);
            $this->assertSame(0700, fileperms($temporaryDirectory) & 0777);
            $this->assertSame(0600, fileperms($temporaryDirectory.'/99-cli-cache.ini') & 0777);
            $this->assertSame("opcache.enable_cli=1\n", file_get_contents($temporaryDirectory.'/99-cli-cache.ini'));
            $process->signal($signal);
            $process->wait();
            $this->assertSame(128 + $signal, $process->getExitCode(), $process->getErrorOutput());
            $this->assertDirectoryDoesNotExist($temporaryDirectory);
            $this->assertSame([], glob($this->directory.'/runtime/*'));
        } finally {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
    }

    /** @return array<string, array{int}> */
    public static function terminationSignals(): array
    {
        return ['interrupt' => [SIGINT], 'terminate' => [SIGTERM]];
    }

    /** @param list<string> $arguments
     * @param  array<string, string|false>  $environment
     */
    private function launch(array $arguments, array $environment): Process
    {
        return new Process([PHP_BINARY, $this->directory.'/scripts/run-php-tests.php', ...$arguments], dirname(__DIR__, 2), $environment, timeout: 20);
    }

    /** @param array<string, string|false> $environment
     * @return array<string, mixed>
     */
    private function baseline(array $environment): array
    {
        $process = new Process([PHP_BINARY, $this->directory.'/artisan', 'test'], $this->directory, $environment, timeout: 15);
        $process->mustRun();

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function defaultScanPath(): string
    {
        $process = new Process([PHP_BINARY, '--ini'], timeout: 15);
        $process->mustRun();
        $this->assertSame(1, preg_match('/^Scan for additional \.ini files in:\h*(.*)$/m', $process->getOutput(), $matches));

        // PHP 8.5 prints the paths of `--ini` inside double quotes.
        return trim(rtrim($matches[1], "\r"), '"');
    }

    /** @param array<string, mixed> $baseline
     * @param  array<string, mixed>  $data
     */
    private function assertPreservedRuntime(array $baseline, array $data): void
    {
        $this->assertTrue($data['opcache']);
        $this->assertSame($baseline['configuration'], $data['configuration']);
        $this->assertSame($baseline['extensions'], $data['extensions']);
        $this->assertSame($baseline['main_ini'], $data['main_ini']);
        $baselineFiles = array_values(array_filter(array_map('trim', explode(',', (string) $baseline['scanned']))));
        $actualFiles = array_values(array_filter(array_map('trim', explode(',', $data['scanned']))));
        $this->assertSame($baselineFiles, array_slice($actualFiles, 0, count($baselineFiles)));
        $this->assertCount(count($baselineFiles) + 1, $actualFiles);
        $scanDirectories = explode(PATH_SEPARATOR, $data['scan_path']);
        $temporaryDirectory = end($scanDirectories);
        $this->assertStringStartsWith($this->directory.'/runtime/lims-php-tests-', $temporaryDirectory);
        $this->assertSame($temporaryDirectory.'/99-cli-cache.ini', end($actualFiles));
        $this->assertDirectoryDoesNotExist($temporaryDirectory);
        $this->assertSame([], glob($this->directory.'/runtime/*'));
    }

    private function removeDirectory(string $path): void
    {
        foreach (new \FilesystemIterator($path) as $entry) {
            if ($entry->isDir() && ! $entry->isLink()) {
                $this->removeDirectory($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($path);
    }
}
