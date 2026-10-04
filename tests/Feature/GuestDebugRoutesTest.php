<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Development shortcuts once answered without sign-in: a report template, a
 * raw database record and two views fed from the first record found.
 */
class GuestDebugRoutesTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{string}>
     */
    public static function retiredPaths(): array
    {
        return ['report template' => ['/sgs'], 'raw record' => ['/cast'], 'label sheet' => ['/labels'], 'analysis sheet' => ['/multiple-sample-analysis']];
    }

    #[DataProvider('retiredPaths')]
    public function test_retired_development_paths_answer_nobody(string $path): void
    {
        $this->get($path)->assertNotFound();
        $this->actingAs(User::factory()->create())->get($path)->assertNotFound();
    }

    public function test_no_route_without_a_name_or_a_guard_serves_a_document_view(): void
    {
        $unguarded = collect(Route::getRoutes())
            ->filter(fn ($route): bool => in_array('GET', $route->methods(), true)
                && $route->getActionName() === 'Closure'
                && ! collect($route->gatherMiddleware())->contains(fn ($middleware): bool => is_string($middleware) && str_starts_with($middleware, 'auth')))
            ->map(fn ($route): string => $route->uri())
            ->filter(fn (string $uri): bool => in_array($uri, ['sgs', 'cast', 'labels', 'multiple-sample-analysis'], true))
            ->values()
            ->all();

        $this->assertSame([], $unguarded);
        $this->assertFalse(view()->exists('PDFs.sgs_report_template'));
    }
}
