<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class PhpCompilationCompatibilityTest extends TestCase
{
    #[DataProvider('applicationSources')]
    public function test_application_sources_compile_without_diagnostics(string $source): void
    {
        $path = dirname(__DIR__, 2).'/'.$source;
        $process = new Process([PHP_BINARY, '-d', 'error_reporting='.E_ALL, '-d', 'display_errors=stderr', '-l', $path], timeout: 15);
        $process->run();
        $diagnostic = mb_substr($process->getErrorOutput().$process->getOutput(), 0, 2000);

        $this->assertTrue($process->isSuccessful(), $diagnostic);
        $this->assertSame('', trim($process->getErrorOutput()), $diagnostic);
        $this->assertSame('No syntax errors detected in '.$path, trim($process->getOutput()), $diagnostic);
    }

    /** @return array<string, array{string}> */
    public static function applicationSources(): array
    {
        return [
            'password confirmation' => ['app/Traits/ConfirmsPasswords.php'],
            'optional board card' => ['app/Http/Controllers/BoardController.php'],
            'project test launcher' => ['scripts/run-php-tests.php'],
        ];
    }
}
