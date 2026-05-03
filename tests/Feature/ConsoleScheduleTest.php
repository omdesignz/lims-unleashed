<?php

namespace Tests\Feature;

use App\Jobs\CheckSupplierAssessmentDeadlines;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ConsoleScheduleTest extends TestCase
{
    public function test_production_operations_commands_are_registered_in_schedule(): void
    {
        $events = app(Schedule::class)->events();
        $commands = collect($events)
            ->map(fn ($event) => trim((string) ($event->description ?? $event->command)))
            ->filter()
            ->values();

        $this->assertTrue($commands->contains(fn (?string $command) => str_contains((string) $command, 'horizon:snapshot')));
        $this->assertTrue($commands->contains(fn (?string $command) => str_contains((string) $command, 'backup:clean')));
        $this->assertTrue($commands->contains(fn (?string $command) => str_contains((string) $command, 'backup:run')));
        $this->assertTrue($commands->contains(fn (?string $command) => str_contains((string) $command, 'backup:monitor')));
        $this->assertTrue($commands->contains(fn (?string $command) => str_contains((string) $command, 'cleanup:old-zips')));
        $this->assertTrue($commands->contains(fn (?string $command) => str_contains((string) $command, CheckSupplierAssessmentDeadlines::class)));
    }
}
