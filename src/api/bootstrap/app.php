<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetLocale;
use App\Support\DuplicateKeyResponder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Session + CSRF for requests coming from the SPA (SANCTUM_STATEFUL_DOMAINS).
        $middleware->statefulApi();

        // There are no Blade login/home pages: both live in the SPA. Without this,
        // `auth` and `guest` look up the `login`/`home` routes and a request
        // without an Accept: application/json header ends in a 500.
        $middleware->redirectGuestsTo(fn () => rtrim(config('app.frontend_url'), '/').'/login');
        $middleware->redirectUsersTo(fn () => rtrim(config('app.frontend_url'), '/').'/');

        // Tenant first: the session user must be looked up already scoped to it
        // (no-op while tenancy is disabled).
        $middleware->prependToGroup('api', IdentifyTenant::class);

        // Response language: user's choice → Accept-Language → app.locale.
        $middleware->api(append: [SetLocale::class]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'super-admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The API always answers JSON ({message} / {message, errors} for 422),
        // even when the client forgets the Accept header.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Duplicate unique key → 409 with the colliding value instead of a 500.
        $exceptions->render(function (UniqueConstraintViolationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return DuplicateKeyResponder::respond($e);
        });
    })->create();
