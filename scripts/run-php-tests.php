<?php

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

$root = dirname(__DIR__);
$temporaryDirectory = null;
$child = null;
$receivedSignal = null;
$exitCode = 1;

$cleanup = static function () use (&$temporaryDirectory): void {
    if ($temporaryDirectory !== null) {
        $file = $temporaryDirectory.'/99-cli-cache.ini';
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir($temporaryDirectory)) {
            rmdir($temporaryDirectory);
        }
        $temporaryDirectory = null;
    }
};
register_shutdown_function($cleanup);

if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    foreach ([SIGINT, SIGTERM] as $signal) {
        pcntl_signal($signal, static function (int $signal) use (&$child, &$receivedSignal): void {
            $receivedSignal ??= $signal;
            if ($child instanceof Process && $child->isRunning()) {
                $child->signal($signal);
            }
        });
    }
}

try {
    if (! extension_loaded('Zend OPcache') || ini_get('opcache.enable') !== '1') {
        throw new RuntimeException('The PHP test launcher requires enabled OPcache. Existing PHP configuration was not changed.');
    }
    $dotenv = is_file($root.'/.env') ? Dotenv::parse(file_get_contents($root.'/.env')) : [];
    $cachePath = getenv('APP_CONFIG_CACHE');
    $cachePath = $cachePath === false ? ($dotenv['APP_CONFIG_CACHE'] ?? $root.'/bootstrap/cache/config.php') : $cachePath;
    if (in_array(strtolower($cachePath), ['null', '(null)'], true)) {
        $cachePath = $root.'/bootstrap/cache/config.php';
    }
    if (! str_starts_with($cachePath, '/') && ! str_starts_with($cachePath, '\\')) {
        $cachePath = $root.'/'.$cachePath;
    }
    if (is_file($cachePath)) {
        throw new RuntimeException('Clear the application configuration cache before running isolated tests. The launcher will not remove it.');
    }
    foreach (['PHP_INI_SCAN_DIR', 'PHPRC'] as $name) {
        if (array_key_exists($name, $dotenv)) {
            throw new RuntimeException('Keep '.$name.' in the shell configuration, not .env; Artisan clears .env variables before starting PHPUnit.');
        }
    }
    $environment = [];
    if (php_ini_loaded_file() !== false) {
        $environment['PHPRC'] = php_ini_loaded_file();
    }

    $scanPath = getenv('PHP_INI_SCAN_DIR');
    if ($scanPath === false) {
        $configuration = new Process([PHP_BINARY, '--ini'], getcwd(), $environment, timeout: 15);
        $configuration->mustRun();
        if ($configuration->getErrorOutput() !== '') {
            throw new RuntimeException('PHP configuration probe reported diagnostics: '.mb_substr($configuration->getErrorOutput(), 0, 1500));
        }
        if (! preg_match('/^Scan for additional \.ini files in:\h*(.*)$/m', $configuration->getOutput(), $matches)) {
            throw new RuntimeException('Cannot determine the existing PHP scan path; refusing to replace PHP configuration.');
        }
        // PHP 8.5 prints the paths of `--ini` inside double quotes; left in place they
        // would read as a relative directory and the default scan path would be lost.
        $scanPath = trim(rtrim($matches[1], "\r"), '"');
        if ($scanPath === '(none)') {
            $scanPath = null;
        }
    }

    $temporaryDirectory = sys_get_temp_dir().'/lims-php-tests-'.bin2hex(random_bytes(12));
    if (! mkdir($temporaryDirectory, 0700)) {
        throw new RuntimeException('Cannot create private test-runtime configuration.');
    }
    $iniPath = $temporaryDirectory.'/99-cli-cache.ini';
    if (file_put_contents($iniPath, "opcache.enable_cli=1\n") === false || ! chmod($iniPath, 0600)) {
        throw new RuntimeException('Cannot prepare private test-runtime configuration.');
    }
    if ($scanPath !== null) {
        $scanPath = implode(PATH_SEPARATOR, array_map(static function (string $directory): string {
            if ($directory === '' || str_starts_with($directory, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $directory)) {
                return $directory;
            }

            return getcwd().DIRECTORY_SEPARATOR.$directory;
        }, explode(PATH_SEPARATOR, $scanPath)));
    }
    $environment['PHP_INI_SCAN_DIR'] = ($scanPath === null ? '' : $scanPath.PATH_SEPARATOR).$temporaryDirectory;

    $probe = new Process([PHP_BINARY, '-r', 'exit(function_exists("opcache_get_status") && opcache_get_status(false) !== false ? 0 : 1);'], $root, $environment, timeout: 15);
    $probe->run();
    if ($probe->getErrorOutput() !== '' || $probe->getOutput() !== '') {
        throw new RuntimeException('PHP runtime probe reported diagnostics: '.mb_substr($probe->getErrorOutput().$probe->getOutput(), 0, 1500));
    }
    if (! $probe->isSuccessful()) {
        throw new RuntimeException('CLI OPcache did not start; refusing an uncached test run.');
    }
    if ($receivedSignal !== null) {
        $exitCode = 128 + $receivedSignal;
    } else {
        $child = new Process([PHP_BINARY, $root.'/artisan', 'test', ...array_slice($argv, 1)], $root, $environment, timeout: null);
        $child->setInput(STDIN);
        $exitCode = $child->run(static function (string $type, string $output): void {
            fwrite($type === Process::ERR ? STDERR : STDOUT, $output);
        });
        if ($receivedSignal !== null) {
            $exitCode = 128 + $receivedSignal;
        }
    }
} catch (ProcessSignaledException $exception) {
    $exitCode = 128 + $exception->getSignal();
} catch (InvalidFileException) {
    fwrite(STDERR, 'PHP test launcher: Cannot parse .env for launch safety. No tests were started.'.PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, 'PHP test launcher: '.mb_substr($exception->getMessage(), 0, 2000).PHP_EOL);
} finally {
    $cleanup();
}

exit($exitCode);
