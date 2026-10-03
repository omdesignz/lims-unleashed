<?php

namespace Tests\Feature;

use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    /**
     * @return array<string, array{int}>
     */
    public static function renderedStatuses(): array
    {
        return ['bad request' => [400], 'forbidden' => [403], 'not found' => [404], 'method not allowed' => [405], 'throttled' => [429], 'server error' => [500], 'unavailable' => [503]];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function bladeViews(): array
    {
        return collect(['401', '402', '403', '404', '419', '429', '500', '503'])->mapWithKeys(fn (string $code): array => [$code => [$code]])->all();
    }

    #[DataProvider('renderedStatuses')]
    public function test_http_errors_render_the_product_error_page_with_the_original_status(int $status): void
    {
        Route::get('/_errors/'.$status, fn () => abort($status));

        $this->get('/_errors/'.$status)
            ->assertStatus($status)
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error')->where('status', $status));
    }

    public function test_unknown_addresses_render_the_product_error_page(): void
    {
        $this->get('/this-address-does-not-exist')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error')->where('status', 404));
    }

    public function test_api_errors_stay_machine_readable(): void
    {
        $this->getJson('/api/this-address-does-not-exist')
            ->assertNotFound()
            ->assertHeader('content-type', 'application/json');
    }

    #[DataProvider('bladeViews')]
    public function test_fallback_error_views_use_the_brand_layout_instead_of_framework_defaults(string $code): void
    {
        (new RegisterErrorViewPaths)();

        $html = view('errors::'.$code)->render();

        $this->assertStringContainsString('data-error-page="vap"', $html);
        $this->assertStringContainsString('Erro '.$code, $html);
        $this->assertStringContainsString('/brand/svg/VAP_Master.svg', $html);
        $this->assertStringContainsString('font-family: "Inter"', $html);
        $this->assertStringNotContainsString('font-weight: 100', $html);
        $this->assertDoesNotMatchRegularExpression('/Not Found|Service Unavailable|Page Expired|Too Many Requests|Server Error|Forbidden|Unauthorized/', $html);
    }
}
