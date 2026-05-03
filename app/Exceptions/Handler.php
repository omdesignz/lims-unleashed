<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
         $response = parent::render($request, $e);

         if ($this->shouldReturnJsonResponse($request)) {
            return $this->normalizeJsonErrorResponse($request, $e, $response);
         }

        //  dd($e->getMessage());

         if($this->shouldRenderCustomErrorPage() &&  in_array($response->status(), [400, 401, 403, 404, 500, 503])) {
            return inertia()->render('Error', [
                'status' => $response->status(),
                'message' => $e->getMessage()
            ])
            ->toResponse($request)
            ->setStatusCode($response->status());
         }

         if($response->status() === 419)
         {
            return back()->with([
                'toast' => [
                    'title' => 'Session Expired',
                    'message' => 'Your session has expired! Please try again.',
                ]
                ]);
         }

         return $response;
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->shouldReturnJsonResponse($request)) {
            return response()->json([
                'message' => 'A autenticação é necessária para concluir este pedido.',
            ], 401);
        }

        if ($request->is('portal') || $request->is('portal/*')) {
            return redirect()->guest('/portal/login');
        }

        return redirect()->guest(route('login'));
    }

    protected function shouldReturnJsonResponse(Request $request): bool
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return true;
        }

        $routeName = (string) optional($request->route())->getName();
        $path = trim($request->path(), '/');
        $accept = (string) $request->headers->get('accept');

        if (
            str_contains($routeName, '.get')
            || str_starts_with($routeName, 'passkeys.')
            || str_starts_with($routeName, 'security.passkeys.')
            || preg_match('#(^|/)(get[A-Z][^/]*|authentication-options)(/|$)#', $path) === 1
            || str_contains($accept, 'application/json')
            || str_contains($accept, 'text/json')
        ) {
            return true;
        }

        return false;
    }

    protected function normalizeJsonErrorResponse(Request $request, Throwable $e, $response): JsonResponse
    {
        if ($response instanceof JsonResponse) {
            return $response;
        }

        $status = method_exists($response, 'status') ? $response->status() : 500;

        if ($e instanceof AuthenticationException) {
            $status = 401;
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Os dados enviados não são válidos.',
                'errors' => $e->errors(),
            ], $e->status);
        }

        return response()->json([
            'message' => $status >= 500
                ? 'Ocorreu um erro interno ao processar o pedido.'
                : ($e->getMessage() ?: 'Não foi possível concluir o pedido.'),
        ], $status);
    }

    protected function shouldRenderCustomErrorPage()
    {
        if(! app()->environment(['local', 'testing']))
        {
            return true;
        }

        return true; 
    }
}
