<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company' => \App\Http\Middleware\EnsureCompanySelected::class,
            'can-permission' => \App\Http\Middleware\CheckPermission::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetActiveCompany::class,
            \App\Http\Middleware\SetCompanyContext::class,
        ]);

        // SECURITY: company-scoping middleware MUST run before Laravel's
        // core SubstituteBindings, which resolves {model} route
        // parameters (e.g. {publication}, {customer}, {vendor}).
        // SubstituteBindings has a fixed high priority by default and
        // would otherwise run BEFORE these — meaning route-model-binding
        // for any BelongsToCompany model could briefly use a stale or
        // unset session company_id on a user's first request in a
        // session (or right after switching companies), before these
        // had a chance to lock it to the correct company. That gap
        // could let a non-Super-Admin resolve another company's record
        // by id. Both middleware set/correct session('company_id'), so
        // both must be pinned ahead of SubstituteBindings.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\SetActiveCompany::class,
        );

        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\EnsureCompanySelected::class,
        );
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })

    ->create();