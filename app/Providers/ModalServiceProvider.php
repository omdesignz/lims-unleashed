<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;

class ModalServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Request::macro('wantsModal', function () {
            return $this->header('X-Modal') ? true : false;
        });

        ResponseFactory::macro('backFromModal', function () {
            // Redirect back to base url
            if ($baseUrl = request()->header('X-Modal-Base-Url')) {
                return redirect($baseUrl);
            }

            // Fallback
            return back();
        });

        ResponseFactory::macro('modal', function (string $component, array $props, string $baseUrl) {
            if (request()->wantsModal()) {
                return inertia()->render($component, $props);
            }

            $baseUrl = request()->header('X-Modal-Base-Url')
                ?: request()->header('referer')
                ?: $baseUrl;

            $request = Request::create($baseUrl);
            $route = Route::getRoutes()->match($request);

            $request->setRouteResolver(fn () => $route);

            $modalPayload = [
                'component' => $component,
                'props' => $props,
                'baseUrl' => $baseUrl,
            ];

            app(SubstituteBindings::class)->handle($request, fn () => null);

            $response = $route->run();

            if ($response instanceof Response) {
                return $response->with('_modal', $modalPayload);
            }

            return $response;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
